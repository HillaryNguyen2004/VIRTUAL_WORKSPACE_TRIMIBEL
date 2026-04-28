import warnings
from dataclasses import dataclass
from typing import Iterable, Sequence

import numpy as np
import pandas as pd


@dataclass(frozen=True)
class ArimaProbConfig:
    arima_order: tuple[int, int, int] = (1, 0, 0)
    min_history: int = 14
    refit_every: int = 7
    clip_01: bool = True
    fallback: str = "rolling_mean_7"  # "rolling_mean_7" | "original"


def _rolling_mean_past(values: np.ndarray, idx: int, window: int = 7) -> float:
    if values.size == 0:
        return 0.0
    if idx <= 0:
        return float(values[0])
    start = max(0, idx - window)
    window_vals = values[start:idx]
    if window_vals.size == 0:
        return float(values[max(0, idx - 1)])
    return float(np.mean(window_vals))


def add_arima_prob_features(
    df: pd.DataFrame,
    *,
    user_col: str = "user_id",
    date_col: str = "full_date",
    binary_cols: Sequence[str] = ("is_late", "checked_in", "had_day_off"),
    out_suffix: str = "_prob",
    config: ArimaProbConfig | None = None,
) -> pd.DataFrame:
    """Add ARIMA-derived probability-like features for binary columns.

    For each employee and each binary column, we create a continuous feature
    (e.g. `checked_in_prob`) that is a 1-step-ahead forecast based only on past
    values (walk-forward). If ARIMA cannot run (insufficient history or fit
    failure), we fall back to a rolling mean of the last 7 days.

    This function mutates `df` in-place and also returns it for convenience.
    """

    if config is None:
        config = ArimaProbConfig()

    missing = [c for c in (user_col, date_col, *binary_cols) if c not in df.columns]
    if missing:
        raise KeyError(f"Missing columns for ARIMA prob features: {missing}")

    # Local import to avoid hard dependency at module import time.
    from statsmodels.tsa.arima.model import ARIMA

    with warnings.catch_warnings():
        warnings.filterwarnings("ignore")

        for col in binary_cols:
            out_col = f"{col}{out_suffix}"
            df[out_col] = np.nan

            total_users = df[user_col].nunique()
            for i, (uid, grp) in enumerate(df.groupby(user_col, sort=False)):
                print(f" [{col}] → Processing ARIMA for User {uid} ({i+1}/{total_users})...", end='\r')
                grp = grp.sort_values(date_col)
                idxs = grp.index.to_numpy()

                # Ensure numeric 0/1 values.
                series = grp[col].astype(float).to_numpy()

                probs = np.zeros_like(series, dtype=float)

                results = None
                last_refit_i = None

                for i in range(series.size):
                    fallback_rm7 = _rolling_mean_past(series, i, window=7)

                    if config.fallback == "original":
                        fallback = float(series[i])
                    else:
                        fallback = fallback_rm7

                    if i < config.min_history:
                        probs[i] = fallback
                        continue

                    # Refit periodically (or on first use)
                    need_refit = (results is None) or (
                        last_refit_i is None
                        or (config.refit_every > 0 and (i - last_refit_i) >= config.refit_every)
                    )

                    if need_refit:
                        history = series[:i]
                        try:
                            model = ARIMA(
                                history,
                                order=config.arima_order,
                                enforce_stationarity=False,
                                enforce_invertibility=False,
                                trend="n",
                            )
                            results = model.fit()
                            last_refit_i = i
                        except Exception:
                            results = None

                    if results is None:
                        probs[i] = fallback
                        continue

                    try:
                        forecast = float(results.forecast(steps=1)[0])
                    except Exception:
                        forecast = fallback

                    probs[i] = forecast

                    # Update filter state with actual observation so next step uses it.
                    try:
                        results = results.append([series[i]], refit=False)
                    except Exception:
                        results = None

                if config.clip_01:
                    probs = np.clip(probs, 0.0, 1.0)

                df.loc[idxs, out_col] = probs

    print("\n✅ ARIMA Transformation complete!")
    return df

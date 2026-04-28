## Plan: ARIMA-Probabilities for Binary Features → LSTM

Replace the raw 0/1 attendance features (`is_late`, `checked_in`, `had_day_off`) with continuous “probability/trend” features computed from ARIMA per employee (e.g., 0.72 meaning “likely 1 today”). Feed these ARIMA-derived features into the existing LSTM classifier. Keep the original binary columns available for derived features like `checkin_streak`.

**Steps**
1. Decide the exact feature substitution
   1. Use ARIMA outputs as new features: `is_late_prob`, `checked_in_prob`, `had_day_off_prob`.
   2. Exclude raw binaries from `FEATURES` (keep them in the dataframe only for calculating `checkin_streak` and any debugging).
2. Add dependency
   1. Add `statsmodels` to `ml/Requirements .txt`.
   2. Document how to install: `pip install statsmodels` (or whatever env you use).
3. Implement ARIMA transformation helper (recommended) with anti-leakage design
   1. Create `ml/arima_binary_prob.py` with a single public function that can be reused by both training and evaluation:
      - `add_arima_prob_features(df, *, user_col='user_id', date_col='full_date', binary_cols=('is_late','checked_in','had_day_off'), out_suffix='_prob', arima_order=(1,0,0), min_history=14, refit_every=7, clip_01=True, fallback='rolling_mean_7')`.
   2. Implement walk-forward computation per employee (prevents leakage):
      - Sort by date within each employee.
      - For each time index t, compute a 1-step-ahead prediction using only history up to t-1.
      - Fit ARIMA on the available past window; to reduce runtime, refit only every `refit_every` steps (reuse parameters between refits).
      - Produce `*_prob` value for each row.
   3. Robustness and constraints:
      - If series length < `min_history`, or ARIMA fails to converge, use fallback:
        - `fallback='rolling_mean_7'` (recommended): use rolling mean of the past 7 days (bounded [0,1], smooth for LSTM).
        - Alternative: fall back to the original 0/1 value (keeps exact signal but less smooth).
      - If `clip_01=True`, clip ARIMA prediction to [0, 1] to keep the “probability” interpretation.

4. Update training script to re-enable binaries + generate prob features
   1. In `ml/train_lstm.py`:
      - Ensure `is_late`, `checked_in`, `had_day_off` are present and numeric 0/1 (uncomment the `.astype(int)` lines).
      - Before `FEATURES` is defined and before scaling, call `add_arima_prob_features(...)`.
      - Re-enable and compute `checkin_streak` using the raw `checked_in` column (as in your original code).
      - Update `FEATURES` to include `is_late_prob`, `checked_in_prob`, `had_day_off_prob` (instead of the raw columns).
      - Keep all other feature engineering logic unchanged.
   2. Confirm `FEATURES` ordering is stable (this affects scaler and inference).

5. Update evaluation to match training exactly
   1. In `ml/evaluate_classifier.py`:
      - Mirror the same ARIMA-prob feature creation (same helper call and parameters).
      - Re-enable `checkin_streak` computation using raw `checked_in`.
      - Update `FEATURES` list to match training exactly.
   2. Confirm that scaler transform uses the same set/order of columns.

6. (Optional but recommended) Update inference path
   1. If `ml/api.py` builds features for real-time predictions, it must be updated too:
      - Either compute ARIMA-prob features online (can be expensive) or store recent history and compute a lightweight fallback (rolling mean) at inference.
      - Ensure the API uses the same `FEATURES` order and same `scaler.pkl`.

**Relevant files**
- `ml/train_lstm.py` — re-enable binaries, compute `*_prob`, update `FEATURES` to use `*_prob`, re-enable `checkin_streak`.
- `ml/evaluate_classifier.py` — mirror the same changes so it stays consistent.
- `ml/arima_binary_prob.py` — new helper shared by train/eval (keeps matching guaranteed).
- `ml/Requirements .txt` — add `statsmodels`.
- `ml/api.py` — optional, only if you run live inference.

**Verification**
1. Sanity prints
   1. For 1–2 employees, print a small table of dates showing raw binary and `*_prob` to verify behavior.
   2. Confirm `*_prob` is not constant and mostly within [0,1].
2. Leakage check (critical for thesis)
   1. Ensure `*_prob` at time t is computed without fitting on data after t.
   2. If you choose the faster but leaky approach (fit on full series then use fittedvalues), document clearly that it is for smoothing only and may inflate metrics.
3. Pipeline consistency
   1. Confirm train/eval `FEATURES` lists match exactly.
   2. Retrain the model, then run evaluation.

**Decisions**
- Recommended: walk-forward ARIMA per employee (leakage-safe).
- Defaults: `ARIMA(1,0,0)`, `min_history=14`, `fallback='rolling_mean_7'`, clip outputs to [0,1].
- Keep `checkin_streak` computed from raw `checked_in`.

**Further Considerations**
1. If ARIMA is unstable/slow, the rolling-mean fallback often works extremely well for binary “probability” smoothing, and is easier to defend in a thesis as an interpretable feature.
2. If you later want a model designed for binary time series, consider a state-space model / HMM, but keep it out of scope for now.

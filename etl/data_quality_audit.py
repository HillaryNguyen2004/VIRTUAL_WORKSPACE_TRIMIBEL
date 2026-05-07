"""
Data Quality Audit — fact_employee_productivity
================================================

Runs four families of checks against the data warehouse and produces a
structured report. Maps to the four dimensions you specified:

  • Tính hiện hành (Currency / Timeliness)   → freshness, recency
  • Tính nhất quán (Consistency)             → cross-column logic
  • Tính toàn vẹn (Integrity)                → nulls, FKs, dupes, range
  • Làm sạch (Cleanliness)                   → outliers, anomalies

Usage:
    python3 data_quality_audit.py

Outputs:
    runs/data_quality_report.json   — structured report (machine-readable)
    runs/data_quality_report.txt    — human-readable summary

This script is read-only: it never modifies the warehouse. Cleaning is
done by a separate script (data_cleaning.py) which reads this report
and applies whichever fixes you authorise.
"""

import json
import os
import sys
from datetime import datetime, date, timedelta

import numpy as np
import pandas as pd
from sqlalchemy import create_engine, text

sys.path.append('../etl')
from config import PG_URL


os.makedirs("runs", exist_ok=True)
engine = create_engine(PG_URL, pool_pre_ping=True)

# ════════════════════════════════════════════════════════════
# Configuration — thresholds for what counts as "good" data
# ════════════════════════════════════════════════════════════
CONFIG = {
    "currency": {
        # How recent should the latest fact row be?
        "max_staleness_days": 2,
    },
    "consistency": {
        # If checked_in=False, hours_worked should be 0
        # If is_late=True, checked_in must be True
        # If had_day_off=True and checked_in=False, productivity should be 0
    },
    "integrity": {
        # Required non-null columns (everything except optional FKs)
        "non_null_required": [
            "employee_sk", "date_sk",
            "hours_worked", "productivity_score",
        ],
        # Valid value ranges
        "ranges": {
            "hours_worked":        (0.0,   24.0),
            "productivity_score":  (0.0,   100.0),
            "avg_task_score":      (0.0,   100.0),
            "avg_task_percentage": (0.0,   100.0),
            "tasks_completed":     (0,     10000),   # ← counts, not percentages
            "tasks_in_progress":   (0,     10000),   # ← counts, not percentages
        },
    },
    "cleanliness": {
        # Anything outside ±3 std-devs from a personal baseline = candidate outlier
        "outlier_zscore_threshold": 3.0,
        # Days where score is exactly 0 but the employee was checked in
        # are suspicious (could be data error, could be legitimate "no task day")
        "zero_score_with_checkin_max_pct": 0.10,
    },
}


# ════════════════════════════════════════════════════════════
# Load all relevant data into one dataframe
# ════════════════════════════════════════════════════════════
def load_warehouse_data() -> pd.DataFrame:
    """Pull the full fact table joined with employee + date dimensions."""
    print("Loading data from warehouse...")
    query = """
        SELECT
            f.employee_sk, f.date_sk, f.dept_sk,
            f.task_sk, f.project_sk, f.phase_sk,
            f.hours_worked, f.is_late, f.checked_in,
            f.had_day_off, f.leave_type,
            f.tasks_completed, f.tasks_in_progress,
            f.avg_task_score, f.avg_task_percentage,
            f.productivity_score,
            f.check_in_time, f.check_out_time,
            e.user_id, e.name AS employee_name, e.role_name,
            d.full_date, d.is_weekend
        FROM fact_employee_productivity f
        JOIN dim_employee e ON f.employee_sk = e.employee_sk
        JOIN dim_date     d ON f.date_sk     = d.date_sk
        ORDER BY e.user_id, d.full_date
    """
    with engine.connect() as conn:
        df = pd.read_sql(query, conn)
    df['full_date'] = pd.to_datetime(df['full_date'])
    print(f"  Loaded {len(df):,} rows.")
    return df


# ════════════════════════════════════════════════════════════
# 1. CURRENCY / TIMELINESS — Tính hiện hành
# ════════════════════════════════════════════════════════════
def check_currency(df: pd.DataFrame) -> dict:
    """How recent is the data? Are there gaps in time coverage?"""
    print("\n[1/4] Checking CURRENCY (hiện hành)...")
    today = pd.Timestamp(date.today())

    latest = df['full_date'].max()
    earliest = df['full_date'].min()
    staleness_days = (today - latest).days

    # Per-employee staleness — flags employees who stopped being recorded
    per_emp_latest = df.groupby('user_id')['full_date'].max()
    stale_employees = per_emp_latest[
        (today - per_emp_latest).dt.days > CONFIG["currency"]["max_staleness_days"]
    ]

    # Time-coverage gaps — find dates within range with no records at all
    expected_dates = set(pd.date_range(earliest, latest, freq='D').date)
    actual_dates   = set(df['full_date'].dt.date)
    missing_dates  = sorted(expected_dates - actual_dates)
    # Filter to weekdays only (weekends are expected to have no records)
    missing_weekdays = [d for d in missing_dates if d.weekday() < 5]

    issues = []
    if staleness_days > CONFIG["currency"]["max_staleness_days"]:
        issues.append({
            "severity": "high",
            "type":     "stale_warehouse",
            "message":  f"Latest fact row is {staleness_days} days old "
                        f"(threshold: {CONFIG['currency']['max_staleness_days']}). "
                        f"ETL may not be running.",
        })
    if len(stale_employees) > 0:
        issues.append({
            "severity": "medium",
            "type":     "stale_employees",
            "message":  f"{len(stale_employees)} employees have no recent records.",
            "details":  [{"user_id": int(uid), "last_seen": str(dt.date())}
                         for uid, dt in stale_employees.items()],
        })
    if len(missing_weekdays) > 5:
        issues.append({
            "severity": "low",
            "type":     "missing_weekdays",
            "message":  f"{len(missing_weekdays)} weekdays in range have no records (could be holidays).",
            "sample":   [str(d) for d in missing_weekdays[:10]],
        })

    print(f"   Latest record: {latest.date()} ({staleness_days} days ago)")
    print(f"   Earliest record: {earliest.date()}")
    print(f"   Date span: {(latest - earliest).days} days")
    print(f"   Stale employees: {len(stale_employees)}")
    print(f"   Missing weekdays: {len(missing_weekdays)}")

    return {
        "checks_run": 3,
        "issues_found": len(issues),
        "issues": issues,
        "stats": {
            "latest_date":        str(latest.date()),
            "earliest_date":      str(earliest.date()),
            "staleness_days":     int(staleness_days),
            "stale_employee_count": int(len(stale_employees)),
            "missing_weekday_count": int(len(missing_weekdays)),
            "total_rows":         int(len(df)),
            "unique_employees":   int(df['user_id'].nunique()),
        },
    }


# ════════════════════════════════════════════════════════════
# 2. CONSISTENCY — Tính nhất quán
# ════════════════════════════════════════════════════════════
def check_consistency(df: pd.DataFrame) -> dict:
    """Cross-column logic: do the columns agree with each other?"""
    print("\n[2/4] Checking CONSISTENCY (nhất quán)...")
    issues = []

    # Rule 1: If is_late=True, then checked_in must be True
    bad = df[(df['is_late'] == True) & (df['checked_in'] == False)]
    if len(bad) > 0:
        issues.append({
            "severity": "high",
            "type":     "late_without_checkin",
            "message":  f"{len(bad)} rows have is_late=True but checked_in=False (impossible).",
            "row_count": int(len(bad)),
            "sample_user_dates": [
                {"user_id": int(r['user_id']), "date": str(r['full_date'].date())}
                for _, r in bad.head(5).iterrows()
            ],
        })

    # Rule 2: If checked_in=False (and not had_day_off), hours_worked should be 0
    bad = df[
        (df['checked_in'] == False) &
        (df['had_day_off'] == False) &
        (df['hours_worked'] > 0)
    ]
    if len(bad) > 0:
        issues.append({
            "severity": "medium",
            "type":     "hours_without_checkin",
            "message":  f"{len(bad)} rows have hours_worked > 0 but no check-in or day-off.",
            "row_count": int(len(bad)),
        })

    # Rule 3: Weekend records — should be rare, since employees normally don't work
    weekend_records = df[df['is_weekend'] == True]
    weekend_with_checkin = weekend_records[weekend_records['checked_in'] == True]
    if len(weekend_with_checkin) > 0:
        issues.append({
            "severity": "low",
            "type":     "weekend_checkins",
            "message":  f"{len(weekend_with_checkin)} weekend records show check-ins (verify if intentional).",
            "row_count": int(len(weekend_with_checkin)),
        })

    # Rule 4: If had_day_off=True, leave_type should be set
    bad = df[(df['had_day_off'] == True) & (df['leave_type'].isna())]
    if len(bad) > 0:
        issues.append({
            "severity": "medium",
            "type":     "dayoff_without_leavetype",
            "message":  f"{len(bad)} rows have had_day_off=True but no leave_type.",
            "row_count": int(len(bad)),
        })

    # Rule 5: If had_day_off=True AND checked_in=False, productivity_score should be 0
    bad = df[
        (df['had_day_off'] == True) &
        (df['checked_in'] == False) &
        (df['productivity_score'] != 0)
    ]
    if len(bad) > 0:
        issues.append({
            "severity": "medium",
            "type":     "dayoff_with_score",
            "message":  f"{len(bad)} day-off rows have non-zero productivity (formula violation).",
            "row_count": int(len(bad)),
        })

    # Rule 6: Hours worked clamped at 24
    bad = df[df['hours_worked'] > 24]
    if len(bad) > 0:
        issues.append({
            "severity": "high",
            "type":     "hours_over_24",
            "message":  f"{len(bad)} rows have hours_worked > 24 (impossible).",
            "row_count": int(len(bad)),
        })

    # Rule 7: Task percentage in [0, 100]
    bad = df[(df['avg_task_percentage'] < 0) | (df['avg_task_percentage'] > 100)]
    if len(bad) > 0:
        issues.append({
            "severity": "high",
            "type":     "task_pct_out_of_range",
            "message":  f"{len(bad)} rows have avg_task_percentage outside [0, 100].",
            "row_count": int(len(bad)),
        })

    print(f"   Consistency violations found: {sum(i.get('row_count', 0) for i in issues)}")

    return {"checks_run": 7, "issues_found": len(issues), "issues": issues}


# ════════════════════════════════════════════════════════════
# 3. INTEGRITY — Tính toàn vẹn
# ════════════════════════════════════════════════════════════
def check_integrity(df: pd.DataFrame) -> dict:
    """Nulls, duplicates, foreign-key validity, range adherence."""
    print("\n[3/4] Checking INTEGRITY (toàn vẹn)...")
    issues = []

    # Required non-null columns
    for col in CONFIG["integrity"]["non_null_required"]:
        if col not in df.columns:
            continue
        nulls = df[col].isna().sum()
        if nulls > 0:
            issues.append({
                "severity": "high",
                "type":     "null_in_required_column",
                "column":   col,
                "message":  f"{nulls} null values in required column '{col}'.",
                "row_count": int(nulls),
            })

    # Range checks
    for col, (lo, hi) in CONFIG["integrity"]["ranges"].items():
        if col not in df.columns:
            continue
        out_of_range = df[(df[col] < lo) | (df[col] > hi)]
        # Exclude NaN — those are handled by the null check above
        out_of_range = out_of_range[out_of_range[col].notna()]
        if len(out_of_range) > 0:
            issues.append({
                "severity": "medium",
                "type":     "value_out_of_range",
                "column":   col,
                "message":  f"{len(out_of_range)} rows have {col} outside [{lo}, {hi}].",
                "row_count": int(len(out_of_range)),
                "actual_min": float(df[col].min()),
                "actual_max": float(df[col].max()),
            })

    # Duplicates: each (employee, date) should be unique
    dupes = df.duplicated(subset=['employee_sk', 'date_sk'], keep=False)
    if dupes.any():
        dupe_count = dupes.sum()
        issues.append({
            "severity": "high",
            "type":     "duplicate_employee_date",
            "message":  f"{dupe_count} rows duplicate the (employee, date) primary grain.",
            "row_count": int(dupe_count),
        })

    # FK integrity: all employee_sk should resolve to a real dim_employee
    with engine.connect() as conn:
        valid_emp_sks = pd.read_sql(
            "SELECT employee_sk FROM dim_employee", conn
        )['employee_sk'].tolist()
    orphan_emp = df[~df['employee_sk'].isin(valid_emp_sks)]
    if len(orphan_emp) > 0:
        issues.append({
            "severity": "high",
            "type":     "orphan_employee_sk",
            "message":  f"{len(orphan_emp)} fact rows reference non-existent employee_sk.",
            "row_count": int(len(orphan_emp)),
        })

    # Coverage: how many employees have <365 days of data?
    days_per_emp = df.groupby('user_id').size()
    sparse_employees = days_per_emp[days_per_emp < 60]   # <60 days = thin history
    if len(sparse_employees) > 0:
        issues.append({
            "severity": "low",
            "type":     "sparse_employee_history",
            "message":  f"{len(sparse_employees)} employees have <60 days of records (insufficient for LSTM training).",
            "details":  [{"user_id": int(uid), "n_days": int(n)}
                         for uid, n in sparse_employees.items()],
        })

    print(f"   Total integrity issues: {len(issues)}")
    print(f"   Total rows affected:    {sum(i.get('row_count', 0) for i in issues):,}")

    return {"checks_run": 5, "issues_found": len(issues), "issues": issues}


# ════════════════════════════════════════════════════════════
# 4. CLEANLINESS — Làm sạch
# ════════════════════════════════════════════════════════════
def check_cleanliness(df: pd.DataFrame) -> dict:
    """Outliers, statistical anomalies, suspicious patterns."""
    print("\n[4/4] Checking CLEANLINESS (làm sạch)...")
    issues = []

    # Personal-baseline outliers per employee
    print("   Computing per-employee z-scores...")
    z_threshold = CONFIG["cleanliness"]["outlier_zscore_threshold"]
    df = df.copy()
    df['_z'] = df.groupby('user_id')['productivity_score'].transform(
        lambda x: np.abs((x - x.mean()) / (x.std() if x.std() > 0 else 1))
    )
    outliers = df[df['_z'] > z_threshold]
    if len(outliers) > 0:
        issues.append({
            "severity": "low",
            "type":     "personal_baseline_outliers",
            "message":  f"{len(outliers)} rows are >{z_threshold} std-devs from "
                        f"the employee's personal mean.",
            "row_count": int(len(outliers)),
            "note": "These may be legitimate exceptional days, not necessarily errors.",
        })

    # Zero-score days with check-ins — could be valid or could indicate
    # missing task data
    zero_with_checkin = df[
        (df['productivity_score'] == 0) & (df['checked_in'] == True)
    ]
    pct = len(zero_with_checkin) / max(len(df[df['checked_in'] == True]), 1)
    if pct > CONFIG["cleanliness"]["zero_score_with_checkin_max_pct"]:
        issues.append({
            "severity": "medium",
            "type":     "high_zero_score_rate",
            "message":  f"{pct*100:.1f}% of checked-in days have score=0 "
                        f"(threshold: {CONFIG['cleanliness']['zero_score_with_checkin_max_pct']*100:.0f}%).",
            "row_count": int(len(zero_with_checkin)),
        })

    # Identical-value sequences — same value repeated for many days in a row.
    # This often indicates a stuck data feed.
    print("   Looking for stuck-value sequences...")
    stuck_runs = []
    for uid, grp in df.groupby('user_id'):
        scores = grp.sort_values('full_date')['productivity_score'].values
        if len(scores) < 7: continue
        # Find runs of identical values
        diff = np.diff(scores)
        run_starts = np.concatenate(([0], np.where(diff != 0)[0] + 1))
        run_lens = np.diff(np.concatenate((run_starts, [len(scores)])))
        for start, length in zip(run_starts, run_lens):
            if length >= 7 and scores[start] != 0:
                # >=7 days of the same non-zero score is suspicious
                stuck_runs.append({
                    "user_id": int(uid),
                    "value":   float(scores[start]),
                    "length":  int(length),
                })
    if stuck_runs:
        issues.append({
            "severity": "medium",
            "type":     "stuck_value_sequences",
            "message":  f"Found {len(stuck_runs)} runs of ≥7 days with identical "
                        f"non-zero productivity scores. May indicate a stuck data source.",
            "row_count": sum(r['length'] for r in stuck_runs),
            "details":   stuck_runs[:10],
        })

    # Distribution skew check — is one class collapsing?
    bins = pd.cut(df['productivity_score'], bins=[-0.1, 50, 80, 100],
                  labels=['Low', 'Medium', 'High'])
    pct_distribution = bins.value_counts(normalize=True).to_dict()
    if pct_distribution.get('Low', 0) < 0.05:
        issues.append({
            "severity": "low",
            "type":     "low_class_underrepresented",
            "message":  f"Only {pct_distribution.get('Low', 0)*100:.1f}% of records "
                        f"are class 'Low'. Classifier may struggle.",
        })

    print(f"   Cleanliness issues found: {len(issues)}")

    return {"checks_run": 4, "issues_found": len(issues), "issues": issues,
            "class_distribution": {k: round(v, 3) for k, v in pct_distribution.items()}}


# ════════════════════════════════════════════════════════════
# Main
# ════════════════════════════════════════════════════════════
def main():
    print("=" * 60)
    print("  DATA QUALITY AUDIT — fact_employee_productivity")
    print("=" * 60)

    df = load_warehouse_data()

    report = {
        "generated_at": datetime.utcnow().isoformat() + "Z",
        "row_count":    int(len(df)),
        "currency":     check_currency(df),
        "consistency":  check_consistency(df),
        "integrity":    check_integrity(df),
        "cleanliness":  check_cleanliness(df),
    }

    # Summary
    total_issues = sum(report[k]["issues_found"] for k in
                       ["currency", "consistency", "integrity", "cleanliness"])
    severity_counts = {"high": 0, "medium": 0, "low": 0}
    for category in ["currency", "consistency", "integrity", "cleanliness"]:
        for issue in report[category]["issues"]:
            sev = issue.get("severity", "low")
            severity_counts[sev] = severity_counts.get(sev, 0) + 1
    report["summary"] = {
        "total_issues":     total_issues,
        "severity_counts":  severity_counts,
        "overall_quality": (
            "good" if severity_counts["high"] == 0 and total_issues < 5
            else "moderate" if severity_counts["high"] < 3
            else "poor"
        ),
    }

    # Write JSON report
    with open("runs/data_quality_report.json", "w") as f:
        json.dump(report, f, indent=2, default=str)

    # Write human-readable text report
    write_text_report(report)

    print("\n" + "=" * 60)
    print(f"  AUDIT COMPLETE")
    print(f"  Total issues:    {total_issues}")
    print(f"    - High:        {severity_counts['high']}")
    print(f"    - Medium:      {severity_counts['medium']}")
    print(f"    - Low:         {severity_counts['low']}")
    print(f"  Overall quality: {report['summary']['overall_quality']}")
    print(f"\n  Reports → runs/data_quality_report.json")
    print(f"  Reports → runs/data_quality_report.txt")
    print("=" * 60)


def write_text_report(report: dict):
    """Write a human-readable summary."""
    lines = []
    lines.append("=" * 70)
    lines.append("DATA QUALITY AUDIT — fact_employee_productivity")
    lines.append("=" * 70)
    lines.append(f"Generated: {report['generated_at']}")
    lines.append(f"Total rows audited: {report['row_count']:,}")
    lines.append(f"Overall quality: {report['summary']['overall_quality'].upper()}")
    lines.append(f"Issues found: {report['summary']['total_issues']} "
                 f"({report['summary']['severity_counts']['high']} high / "
                 f"{report['summary']['severity_counts']['medium']} medium / "
                 f"{report['summary']['severity_counts']['low']} low)")
    lines.append("")

    section_titles = {
        "currency":    "1. CURRENCY (Tính hiện hành)",
        "consistency": "2. CONSISTENCY (Tính nhất quán)",
        "integrity":   "3. INTEGRITY (Tính toàn vẹn)",
        "cleanliness": "4. CLEANLINESS (Làm sạch)",
    }
    for key, title in section_titles.items():
        section = report[key]
        lines.append("─" * 70)
        lines.append(title)
        lines.append("─" * 70)
        lines.append(f"Checks run:   {section['checks_run']}")
        lines.append(f"Issues found: {section['issues_found']}")
        lines.append("")
        if not section['issues']:
            lines.append("  ✓ No issues detected.")
        else:
            for i, issue in enumerate(section['issues'], 1):
                sev = issue.get('severity', 'low').upper()
                lines.append(f"  [{sev}] {i}. {issue['type']}")
                lines.append(f"        {issue['message']}")
        lines.append("")

    with open("runs/data_quality_report.txt", "w") as f:
        f.write("\n".join(lines))


if __name__ == "__main__":
    main()
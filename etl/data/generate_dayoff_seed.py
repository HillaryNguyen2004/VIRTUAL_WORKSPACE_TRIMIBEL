import argparse
import random
from datetime import datetime, timedelta
from pathlib import Path

# Defaults (matching generate_full_seed.py)
DEFAULT_NUM_USERS = 30
DEFAULT_START_DATE = datetime(2018, 1, 1)
DEFAULT_END_DATE = datetime(2026, 3, 30)

# Realistic day-off reasons
DAYOFF_REASONS = [
    "Personal leave",
    "Sick leave",
    "Family emergency",
    "Medical appointment",
    "Maternity leave",
    "Wedding ceremony",
    "Funeral",
    "House moving",
    "Child care",
    "Doctor visit",
    "Dental appointment",
    "Car repair",
    "Home maintenance",
    "Annual leave",
    "Study leave",
]

HALF_DAY_REASONS = [
    "Medical appointment",
    "Dental appointment",
    "Personal errand",
    "School meeting",
    "Doctor visit",
    "Car service",
]


def get_weekdays_in_range(start: datetime, end: datetime) -> list[datetime]:
    """Get all weekdays (Mon-Fri) between start and end dates."""
    weekdays = []
    current = start
    while current <= end:
        if current.weekday() < 5:  # 0-4 = Mon-Fri
            weekdays.append(current)
        current += timedelta(days=1)
    return weekdays


def generate_sql(
    *,
    num_users: int,
    start_date: datetime,
    end_date: datetime,
    dayoff_percentage: float = 0.05,
    halfday_percentage: float = 0.02,
) -> str:
    """
    Generate SQL for dayoff_requests table.
    
    Args:
        num_users: Number of users in system
        start_date: Start date for range
        end_date: End date for range
        dayoff_percentage: ~% of weekdays that should be full day-offs
        halfday_percentage: ~% of weekdays that should be half day-offs
    """
    sql: list[str] = []

    sql.append("SET FOREIGN_KEY_CHECKS=0;")
    sql.append("TRUNCATE TABLE day_off_requests;")
    sql.append("SET FOREIGN_KEY_CHECKS=1;\n")

    weekdays = get_weekdays_in_range(start_date, end_date)
    id_counter = 1

    for user_id in range(1, num_users + 1):
        for date in weekdays:
            # Randomly decide if this is a day-off
            rand = random.random()

            # ~5% chance of full day off
            if rand < dayoff_percentage:
                leave_type = "OFF_FULL"
                half_day_period = None
                reason = random.choice(DAYOFF_REASONS)

                # Most are approved in seed
                status = random.choices(["APPROVED", "PENDING"], weights=[85, 15])[0]

                sql.append(
                    "INSERT INTO day_off_requests "
                    "(id, user_id, date, leave_type, reason, status, half_day_period, created_at, updated_at) "
                    "VALUES "
                    f"({id_counter}, {user_id}, '{date.date()}', '{leave_type}', '{reason}', '{status}', NULL, '{date}', '{date}');"
                )
                id_counter += 1

            # ~2% chance of half day off (if not already full day off)
            elif rand < (dayoff_percentage + halfday_percentage):
                leave_type = "OFF_HALF"
                half_day_period = random.choice(["AM", "PM"])
                reason = random.choice(HALF_DAY_REASONS)
                status = random.choices(["APPROVED", "PENDING"], weights=[90, 10])[0]

                sql.append(
                    "INSERT INTO day_off_requests "
                    "(id, user_id, date, leave_type, reason, status, half_day_period, created_at, updated_at) "
                    "VALUES "
                    f"({id_counter}, {user_id}, '{date.date()}', '{leave_type}', '{reason}', '{status}', '{half_day_period}', '{date}', '{date}');"
                )
                id_counter += 1

    return "\n".join(sql) + "\n"


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Generate dayoff_seed.sql for day_off_requests table"
    )
    parser.add_argument(
        "--output", default="dayoff_seed.sql", help="Output .sql file path"
    )
    parser.add_argument("--seed", type=int, default=42, help="Random seed")
    parser.add_argument("--num-users", type=int, default=DEFAULT_NUM_USERS)
    parser.add_argument(
        "--start-date",
        default=DEFAULT_START_DATE.strftime("%Y-%m-%d"),
        help="Start date (YYYY-MM-DD)",
    )
    parser.add_argument(
        "--end-date",
        default=DEFAULT_END_DATE.strftime("%Y-%m-%d"),
        help="End date (YYYY-MM-DD)",
    )
    parser.add_argument(
        "--dayoff-percent",
        type=float,
        default=0.05,
        help="Percentage of weekdays as full day-offs (0.0-1.0)",
    )
    parser.add_argument(
        "--halfday-percent",
        type=float,
        default=0.02,
        help="Percentage of weekdays as half day-offs (0.0-1.0)",
    )

    args = parser.parse_args()

    random.seed(args.seed)

    start_date = datetime.strptime(args.start_date, "%Y-%m-%d")
    end_date = datetime.strptime(args.end_date, "%Y-%m-%d")

    out_path = Path(args.output)
    out_path.parent.mkdir(parents=True, exist_ok=True)

    sql_text = generate_sql(
        num_users=args.num_users,
        start_date=start_date,
        end_date=end_date,
        dayoff_percentage=args.dayoff_percent,
        halfday_percentage=args.halfday_percent,
    )

    out_path.write_text(sql_text, encoding="utf-8")
    print(f"✅ Day-off SQL generated: {out_path}")
    print(f"   Users: {args.num_users}")
    print(f"   Date range: {start_date.date()} to {end_date.date()}")
    print(
        f"   Full day-offs: ~{args.dayoff_percent*100:.1f}% of weekdays"
    )
    print(
        f"   Half day-offs: ~{args.halfday_percent*100:.1f}% of weekdays"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

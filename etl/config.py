import os
from dotenv import load_dotenv

load_dotenv()

MYSQL_CONFIG = {
    "host": os.getenv("MYSQL_DB_HOST", "localhost"),
    "user": os.getenv("MYSQL_DB_USERNAME", "root"),
    "port": int(os.getenv("MYSQL_DB_PORT", 3306)),
    "password": os.getenv("MYSQL_DB_PASSWORD", ""),
    "database": os.getenv("MYSQL_DB_DATABASE", "manage_user")
}

PG_CONFIG = {
    "host": os.getenv("PG_DB_HOST", "localhost"),
    "dbname": os.getenv("PG_DB_NAME", "dw_productivity"),
    "port": int(os.getenv("PG_DB_PORT", 5432)),
    "user": os.getenv("PG_DB_USER", "postgres"),
    "password": os.getenv("PG_DB_PASSWORD", "mkhoa6868")
}

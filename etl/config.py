import os
from urllib.parse import urlparse, unquote
from dotenv import load_dotenv

load_dotenv()

MYSQL_CONFIG = {
    "host": os.getenv("MYSQL_DB_HOST", "localhost"),
    "user": os.getenv("MYSQL_DB_USERNAME", "root"),
    "port": int(os.getenv("MYSQL_DB_PORT", 3306)),
    "password": os.getenv("MYSQL_DB_PASSWORD", ""),
    "database": os.getenv("MYSQL_DB_DATABASE", "manage_user")
}

_pg_url = os.getenv("PG_URL")

if _pg_url:
    _p = urlparse(_pg_url)
    PG_CONFIG = {
        "host": _p.hostname,
        "dbname": _p.path.lstrip("/"),
        "port": _p.port or 5432,
        "user": _p.username,
        "password": unquote(_p.password or ""),
    }
    PG_URL = _pg_url
else:
    PG_CONFIG = {
        "host": os.getenv("PG_DB_HOST", "localhost"),
        "dbname": os.getenv("PG_DB_NAME", "dw_productivity"),
        "port": int(os.getenv("PG_DB_PORT", 5432)),
        "user": os.getenv("PG_DB_USER", "postgres"),
        "password": os.getenv("PG_DB_PASSWORD", "123456"),
    }
    PG_URL = (
        f"postgresql+psycopg2://{PG_CONFIG['user']}:{PG_CONFIG['password']}"
        f"@{PG_CONFIG['host']}:{PG_CONFIG['port']}/{PG_CONFIG['dbname']}"
    )

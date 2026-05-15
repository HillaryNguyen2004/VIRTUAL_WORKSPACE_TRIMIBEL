from __future__ import annotations

import logging
import tempfile
from contextlib import contextmanager
from pathlib import Path
from typing import Generator

import boto3
from botocore.exceptions import BotoCoreError, ClientError

from .config import settings

log = logging.getLogger(__name__)


def _s3_client():
    return boto3.client(
        "s3",
        region_name=settings.aws_region,
        aws_access_key_id=settings.aws_access_key_id,
        aws_secret_access_key=settings.aws_secret_access_key,
    )


@contextmanager
def download_s3_file(
    s3_key: str,
    bucket: str | None = None,
    suffix: str | None = None,
) -> Generator[Path, None, None]:
    """
    Download an S3 object to a local temp file and yield its Path.
    The temp file is deleted on exit regardless of errors.

    Usage:
        with download_s3_file("documents/42/document.docx") as local_path:
            chunks, metas = chunk_file(local_path)
    """
    resolved_bucket = bucket or settings.aws_bucket
    if not resolved_bucket:
        raise ValueError("AWS_BUCKET is not configured")

    if not s3_key:
        raise ValueError("s3_key must not be empty")

    ext = suffix or Path(s3_key).suffix or ""
    client = _s3_client()

    with tempfile.NamedTemporaryFile(suffix=ext, delete=False) as tmp:
        tmp_path = Path(tmp.name)

    try:
        log.debug("Downloading s3://%s/%s → %s", resolved_bucket, s3_key, tmp_path)
        client.download_file(resolved_bucket, s3_key, str(tmp_path))
        yield tmp_path
    except (BotoCoreError, ClientError) as exc:
        raise RuntimeError(f"S3 download failed for key={s3_key!r}: {exc}") from exc
    finally:
        tmp_path.unlink(missing_ok=True)


def object_exists(s3_key: str, bucket: str | None = None) -> bool:
    """Return True if the S3 object exists."""
    resolved_bucket = bucket or settings.aws_bucket
    try:
        _s3_client().head_object(Bucket=resolved_bucket, Key=s3_key)
        return True
    except ClientError:
        return False

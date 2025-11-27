from __future__ import annotations
from lingua import Language, LanguageDetectorBuilder

_LANGS = [
    Language.ENGLISH, Language.VIETNAMESE, Language.JAPANESE,
    Language.KOREAN, Language.CHINESE, Language.FRENCH,
    Language.GERMAN, Language.SPANISH, Language.PORTUGUESE
]
_DETECTOR = LanguageDetectorBuilder.from_languages(*_LANGS).build()

def detect_lang(text: str, fallback: str = "en") -> str:
    lang = _DETECTOR.detect_language_of(text)
    return (lang.iso_code_639_1.name.lower() if lang else fallback)

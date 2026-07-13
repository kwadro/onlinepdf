from __future__ import annotations

import inflect
from jinja2 import Environment, FileSystemLoader, select_autoescape

from .config import BASE

_p = inflect.engine()
_jinja_env: Environment | None = None


def singularize(word: str) -> str:
    result = _p.singular_noun(word)
    return result if result else word


def capitalize(word: str) -> str:
    return word[:1].upper() + word[1:] if word else ""


def to_pascal_case(value: str) -> str:
    return "".join(word.capitalize() for word in value.split("_"))


def get_jinja_env() -> Environment:
    global _jinja_env
    if _jinja_env is None:
        _jinja_env = Environment(
            loader=FileSystemLoader(BASE / "templates"),
            autoescape=select_autoescape(default=False),
            keep_trailing_newline=True,
        )
        _jinja_env.filters["pascal"] = to_pascal_case
        _jinja_env.filters["singularize"] = singularize
        _jinja_env.filters["capitalize"] = capitalize
    return _jinja_env


def render_template(template_file: str, **ctx) -> str:
    template = get_jinja_env().get_template(template_file)
    return template.render(**ctx)

from __future__ import annotations

import json
import os
import re
import textwrap
import urllib.error
import urllib.request
from dataclasses import dataclass
from pathlib import Path
from typing import Any

import yaml

from recipe_catalog.builder import PROJECT_ROOT, slugify, yes_no


PROMPT_DIR = Path(__file__).resolve().parent / "prompts"
DEFAULT_UNITS = ["г.", "кг.", "л.", "мл.", "шт.", "ст.л.", "ч.л.", "щіпка", "склянка"]


@dataclass
class RecipeGeneratorConfig:
    api_key: str
    model: str = "gpt-4o-mini"
    base_url: str = "https://api.openai.com/v1"
    timeout: int = 120


class RecipeGenerator:
    def __init__(self, config: RecipeGeneratorConfig | None = None) -> None:
        self.config = config or RecipeGeneratorConfig(api_key=self._resolve_api_key())

    def generate(
        self,
        title: str,
        *,
        site: str = "symfony.local",
        locale: str = "uk",
        category: str | None = None,
        categories: list[str] | None = None,
        cuisine: str = "Українська",
        author_email: str = "kwadro2010@gmail.com",
        servings: int | None = None,
        notes: str | None = None,
        publish_ready: bool = True,
    ) -> dict[str, Any]:
        title = title.strip()
        if not title:
            raise ValueError("Recipe title is required.")

        selected_categories = categories or ([category] if category else ["Перші страви"])
        payload = self._call_model(
            title=title,
            locale=locale,
            categories=selected_categories,
            cuisine=cuisine,
            servings=servings,
            notes=notes,
            publish_ready=publish_ready,
        )
        return self._normalize_generated_payload(
            payload,
            site=site,
            locale=locale,
            categories=selected_categories,
            cuisine=cuisine,
            author_email=author_email,
            servings=servings,
        )

    def save_yaml(self, data: dict[str, Any], path: Path) -> None:
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(
            yaml.safe_dump(data, allow_unicode=True, sort_keys=False),
            encoding="utf-8",
        )

    @classmethod
    def from_env(cls) -> RecipeGenerator:
        return cls(
            RecipeGeneratorConfig(
                api_key=cls._resolve_api_key(),
                model=cls._resolve_env("OPENAI_MODEL", "gpt-4o-mini"),
                base_url=cls._resolve_env("OPENAI_BASE_URL", "https://api.openai.com/v1"),
            )
        )

    @staticmethod
    def _resolve_env(name: str, default: str | None = None) -> str:
        value = os.environ.get(name)
        if value:
            return value

        for env_file in (PROJECT_ROOT / ".env.local", PROJECT_ROOT / ".env"):
            if not env_file.is_file():
                continue
            for line in env_file.read_text(encoding="utf-8").splitlines():
                line = line.strip()
                if not line or line.startswith("#") or "=" not in line:
                    continue
                key, raw_value = line.split("=", 1)
                if key.strip() != name:
                    continue
                cleaned = raw_value.strip().strip('"').strip("'")
                if cleaned:
                    return cleaned

        if default is not None:
            return default

        raise ValueError(f"Environment variable {name} is not set.")

    @classmethod
    def _resolve_api_key(cls) -> str:
        return cls._resolve_env("OPENAI_API_KEY")

    def _call_model(
        self,
        *,
        title: str,
        locale: str,
        categories: list[str],
        cuisine: str,
        servings: int | None,
        notes: str | None,
        publish_ready: bool,
    ) -> dict[str, Any]:
        system_prompt = (PROMPT_DIR / "recipe_generate_system.txt").read_text(encoding="utf-8")
        user_prompt = self._build_user_prompt(
            title=title,
            locale=locale,
            categories=categories,
            cuisine=cuisine,
            servings=servings,
            notes=notes,
            publish_ready=publish_ready,
        )

        request_body = {
            "model": self.config.model,
            "temperature": 0.7,
            "response_format": {"type": "json_object"},
            "messages": [
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": user_prompt},
            ],
        }

        url = f"{self.config.base_url.rstrip('/')}/chat/completions"
        request = urllib.request.Request(
            url,
            data=json.dumps(request_body).encode("utf-8"),
            headers={
                "Authorization": f"Bearer {self.config.api_key}",
                "Content-Type": "application/json",
            },
            method="POST",
        )

        try:
            with urllib.request.urlopen(request, timeout=self.config.timeout) as response:
                raw = json.loads(response.read().decode("utf-8"))
        except urllib.error.HTTPError as error:
            details = error.read().decode("utf-8", errors="replace")
            raise RuntimeError(f"LLM API error ({error.code}): {details}") from error

        content = raw["choices"][0]["message"]["content"]
        parsed = self._parse_json_content(content)
        if not isinstance(parsed, dict):
            raise RuntimeError("LLM response is not a JSON object.")
        return parsed

    def _build_user_prompt(
        self,
        *,
        title: str,
        locale: str,
        categories: list[str],
        cuisine: str,
        servings: int | None,
        notes: str | None,
        publish_ready: bool,
    ) -> str:
        return textwrap.dedent(
            f"""
            Create a publish-ready recipe for a Ukrainian cooking website.

            Input:
            - title: {title}
            - locale: {locale}
            - categories: {", ".join(categories)}
            - cuisine: {cuisine}
            - servings: {servings or "choose realistic amount"}
            - extra notes: {notes or "none"}
            - publish_ready: {"Yes" if publish_ready else "No"}

            Requirements:
            - Write in Ukrainian if locale is uk.
            - Return valid JSON only.
            - Include detailed ingredient groups and at least 5 detailed cooking steps.
            - Use only these unit short names when possible: {", ".join(DEFAULT_UNITS)}.
            - Make short_description 2-3 lines for cards.
            - Make description a full SEO-friendly paragraph.
            - Fill meta_title and meta_description.
            """
        ).strip()

    @staticmethod
    def _parse_json_content(content: str) -> Any:
        content = content.strip()
        if content.startswith("```"):
            content = re.sub(r"^```(?:json)?\s*", "", content)
            content = re.sub(r"\s*```$", "", content)

        return json.loads(content)

    def _normalize_generated_payload(
        self,
        payload: dict[str, Any],
        *,
        site: str,
        locale: str,
        categories: list[str],
        cuisine: str,
        author_email: str,
        servings: int | None,
    ) -> dict[str, Any]:
        recipe = payload.get("recipe")
        if not isinstance(recipe, dict):
            raise RuntimeError('LLM response must contain object "recipe".')

        translation = recipe.get("translation")
        if not isinstance(translation, dict):
            translations = recipe.get("translations")
            if isinstance(translations, list) and translations:
                translation = translations[0]
        if not isinstance(translation, dict):
            raise RuntimeError('LLM response recipe must contain "translation".')

        name = str(translation.get("name") or recipe.get("name") or "").strip()
        if not name:
            raise RuntimeError("Generated recipe is missing name.")

        slug = str(translation.get("slug") or slugify(name))
        translation.setdefault("locale", locale)
        translation.setdefault("slug", slug)
        translation.setdefault("cuisine", cuisine)
        translation.setdefault("author_email", author_email)
        translation["is_active"] = yes_no(translation.get("is_active", "Yes"), "Yes")
        translation["publish"] = yes_no(translation.get("publish", "Yes"), "Yes")
        translation["confirmation"] = yes_no(translation.get("confirmation", "No"), "No")
        translation["is_popular"] = yes_no(translation.get("is_popular", "No"), "No")

        recipe.setdefault("external_key", slugify(name))
        recipe.setdefault("categories", categories)
        recipe["categories"] = categories
        if servings is not None:
            recipe["servings"] = servings

        reference_data = payload.get("reference_data")
        if not isinstance(reference_data, dict):
            reference_data = {}

        return {
            "site": site,
            "reference_data": reference_data,
            "recipe": {
                **recipe,
                "translation": translation,
            },
        }

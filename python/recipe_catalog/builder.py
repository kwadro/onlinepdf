from __future__ import annotations

import json
import re
import unicodedata
from copy import deepcopy
from pathlib import Path
from typing import Any


PROJECT_ROOT = Path(__file__).resolve().parents[2]
DEFAULT_TEMPLATE = PROJECT_ROOT / "data/templates/recipe-catalog.template.json"
DEFAULT_SITE = "symfony.local"


def yes_no(value: Any, default: str = "No") -> str:
    if isinstance(value, bool):
        return "Yes" if value else "No"

    normalized = str(value).strip().lower()
    if normalized in {"yes", "1", "true"}:
        return "Yes"
    if normalized in {"no", "0", "false"}:
        return "No"

    return default


def slugify(value: str) -> str:
    normalized = unicodedata.normalize("NFKD", value)
    ascii_text = normalized.encode("ascii", "ignore").decode("ascii")
    slug = re.sub(r"[^a-z0-9]+", "-", ascii_text.lower()).strip("-")

    return slug or "recipe"


class RecipeCatalogBuilder:
    def __init__(self, site: str = DEFAULT_SITE, schema_version: int = 1) -> None:
        self.site = site
        self.schema_version = schema_version
        self.reference_data: dict[str, list[dict[str, Any]]] = {
            "units": [],
            "ingredients": [],
            "categories": [],
        }
        self.recipes: list[dict[str, Any]] = []

    @classmethod
    def from_template(cls, template_path: Path | None = None, site: str | None = None) -> RecipeCatalogBuilder:
        path = template_path or DEFAULT_TEMPLATE
        payload = json.loads(path.read_text(encoding="utf-8"))
        builder = cls(site=site or payload.get("site", DEFAULT_SITE))
        builder.reference_data = deepcopy(payload.get("reference_data", builder.reference_data))
        builder.recipes = deepcopy(payload.get("recipes", []))
        return builder

    @classmethod
    def from_simple_yaml(cls, data: dict[str, Any]) -> RecipeCatalogBuilder:
        site = str(data.get("site", DEFAULT_SITE))
        builder = cls(site=site)

        reference_data = data.get("reference_data")
        if isinstance(reference_data, dict):
            builder._normalize_reference_data(reference_data)

        recipe_data = data.get("recipe")
        if isinstance(recipe_data, dict):
            builder.add_recipe(recipe_data)

        recipes = data.get("recipes")
        if isinstance(recipes, list):
            for recipe in recipes:
                if isinstance(recipe, dict):
                    builder.add_recipe(recipe)

        return builder

    def add_unit(self, name: str, short_name: str | None = None) -> None:
        short = short_name or name
        if not any(item["short_name"] == short for item in self.reference_data["units"]):
            self.reference_data["units"].append({"name": name, "short_name": short})

    def add_ingredient(self, name: str) -> None:
        if not any(item["name"] == name for item in self.reference_data["ingredients"]):
            self.reference_data["ingredients"].append(
                {"name": name, "sku": None, "url": None, "price": None}
            )

    def add_category(
        self,
        name: str,
        slug: str | None = None,
        position: int | None = None,
        is_active: str = "Yes",
        parent: str | None = None,
    ) -> None:
        if any(item["name"] == name for item in self.reference_data["categories"]):
            return

        self.reference_data["categories"].append(
            {
                "name": name,
                "slug": slug or slugify(name),
                "position": position or len(self.reference_data["categories"]) + 1,
                "is_active": is_active,
                "parent": parent,
            }
        )

    def add_recipe(self, recipe: dict[str, Any]) -> dict[str, Any]:
        normalized = self._normalize_recipe(recipe)
        self.recipes.append(normalized)
        self._collect_reference_data_from_recipe(normalized)
        return normalized

    def to_dict(self, include_reference_data: bool = True) -> dict[str, Any]:
        payload: dict[str, Any] = {
            "schema_version": self.schema_version,
            "site": self.site,
            "recipes": deepcopy(self.recipes),
        }
        if include_reference_data and any(self.reference_data.values()):
            payload["reference_data"] = deepcopy(self.reference_data)
        return payload

    def to_json(self, include_reference_data: bool = True, indent: int = 2) -> str:
        return json.dumps(
            self.to_dict(include_reference_data=include_reference_data),
            ensure_ascii=False,
            indent=indent,
        ) + "\n"

    def save(self, path: Path, include_reference_data: bool = True) -> None:
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(self.to_json(include_reference_data=include_reference_data), encoding="utf-8")

    def _normalize_reference_data(self, reference_data: dict[str, Any]) -> None:
        for unit in reference_data.get("units", []):
            if isinstance(unit, str):
                self.add_unit(unit, unit)
            elif isinstance(unit, dict):
                self.add_unit(str(unit.get("name", "")), str(unit.get("short_name") or unit.get("name", "")))

        for ingredient in reference_data.get("ingredients", []):
            if isinstance(ingredient, str):
                self.add_ingredient(ingredient)
            elif isinstance(ingredient, dict) and ingredient.get("name"):
                self.add_ingredient(str(ingredient["name"]))

        for category in reference_data.get("categories", []):
            if isinstance(category, str):
                self.add_category(category)
            elif isinstance(category, dict) and category.get("name"):
                self.add_category(
                    str(category["name"]),
                    category.get("slug"),
                    category.get("position"),
                    str(category.get("is_active", "Yes")),
                    category.get("parent"),
                )

    def _normalize_recipe(self, recipe: dict[str, Any]) -> dict[str, Any]:
        translations_raw = recipe.get("translations")
        if translations_raw is None and "translation" in recipe:
            translations_raw = [recipe["translation"]]

        translations: list[dict[str, Any]] = []
        for index, translation in enumerate(translations_raw or []):
            if isinstance(translation, dict):
                translations.append(self._normalize_translation(translation, index + 1))

        if not translations:
            raise ValueError("Recipe must contain at least one translation.")

        primary_slug = translations[0].get("slug") or slugify(str(translations[0].get("name", "recipe")))
        external_key = recipe.get("external_key") or primary_slug

        normalized = {
            "external_key": external_key,
            "position": recipe.get("position", len(self.recipes) + 1),
            "prep_time_min": recipe.get("prep_time_min"),
            "cook_time_min": recipe.get("cook_time_min"),
            "servings": recipe.get("servings"),
            "image": recipe.get("image"),
            "categories": list(recipe.get("categories") or []),
            "translations": translations,
        }
        if recipe.get("id") is not None:
            normalized["id"] = recipe.get("id")

        return normalized

    def _normalize_translation(self, translation: dict[str, Any], fallback_position: int) -> dict[str, Any]:
        name = str(translation.get("name", "")).strip()
        if not name:
            raise ValueError("Translation name is required.")

        slug = translation.get("slug") or slugify(name)
        groups_raw = translation.get("group_components")
        if groups_raw is None:
            groups_raw = translation.get("groups", [])

        steps_raw = translation.get("steps", [])

        return {
            "locale": translation.get("locale", "uk"),
            "name": name,
            "slug": slug,
            "is_active": yes_no(translation.get("is_active", "Yes"), "Yes"),
            "publish": yes_no(translation.get("publish", "Yes"), "Yes"),
            "confirmation": yes_no(translation.get("confirmation", "No"), "No"),
            "is_popular": yes_no(translation.get("is_popular", "No"), "No"),
            "meta_title": translation.get("meta_title"),
            "meta_description": translation.get("meta_description"),
            "short_description": translation.get("short_description"),
            "description": translation.get("description"),
            "cuisine": translation.get("cuisine"),
            "notes": translation.get("notes"),
            "facebook_image": translation.get("facebook_image"),
            "author_email": translation.get("author_email"),
            "group_components": [
                self._normalize_group(group, group_index + 1)
                for group_index, group in enumerate(groups_raw or [])
                if isinstance(group, dict)
            ],
            "steps": [
                self._normalize_step(step, step_index + 1)
                for step_index, step in enumerate(steps_raw or [])
                if isinstance(step, dict)
            ],
        }

    def _normalize_group(self, group: dict[str, Any], fallback_position: int) -> dict[str, Any]:
        components_raw = group.get("components", [])
        return {
            "name": group.get("name", f"Group {fallback_position}"),
            "position": group.get("position", fallback_position),
            "components": [
                self._normalize_component(component, component_index + 1)
                for component_index, component in enumerate(components_raw)
                if isinstance(component, dict)
            ],
        }

    def _normalize_component(self, component: dict[str, Any], fallback_position: int) -> dict[str, Any]:
        ingredient = str(component.get("ingredient", "")).strip()
        unit = str(component.get("unit", "")).strip()
        if not ingredient or not unit:
            raise ValueError("Each component requires ingredient and unit.")

        return {
            "position": component.get("position", fallback_position),
            "ingredient": ingredient,
            "unit": unit,
            "quantity": int(component.get("quantity", 0)),
        }

    def _normalize_step(self, step: dict[str, Any], fallback_position: int) -> dict[str, Any]:
        answer = step.get("answer")
        if not answer:
            raise ValueError("Each step requires answer text.")

        return {
            "name": step.get("name", f"Крок {fallback_position}"),
            "position": step.get("position", fallback_position),
            "question": step.get("question"),
            "answer": answer,
            "image": step.get("image"),
        }

    def _collect_reference_data_from_recipe(self, recipe: dict[str, Any]) -> None:
        for category_name in recipe.get("categories", []):
            if category_name:
                self.add_category(str(category_name))

        for translation in recipe.get("translations", []):
            for group in translation.get("group_components", []):
                for component in group.get("components", []):
                    self.add_ingredient(str(component["ingredient"]))
                    unit_short = str(component["unit"])
                    self.add_unit(unit_short, unit_short)

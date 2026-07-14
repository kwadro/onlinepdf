from __future__ import annotations

from pathlib import Path
from typing import Any

import yaml

BASE = Path(__file__).resolve().parent.parent
DEFAULT_CONFIG = BASE / "structure-recipe.yaml"

KNOWN_FIELD_TYPES = {
    "integer",
    "string",
    "datetime",
    "datetime_immutable",
    "date",
    "float",
    "boolean",
    "text",
    "choice",
    "image",
    "relation",
    "select",
    "money",
    "enum",
    "json",
}


def _load_yaml_file(path: Path) -> Any:
    with open(path, encoding="utf-8") as handle:
        return yaml.safe_load(handle)


def _load_entities_from_dir(entities_dir: Path) -> dict[str, dict]:
    entities: dict[str, dict] = {}
    if not entities_dir.is_dir():
        raise FileNotFoundError(f"Entities directory not found: {entities_dir}")

    for entity_file in sorted(entities_dir.glob("*.yaml")):
        entity_name = entity_file.stem
        entity_data = _load_yaml_file(entity_file)
        if not isinstance(entity_data, dict):
            raise ValueError(f"Entity file must be a mapping: {entity_file}")
        entities[entity_name] = entity_data

    return entities


def _apply_meta_block(entity: dict) -> dict:
    """Support optional meta block while keeping legacy top-level keys."""
    meta = entity.get("meta")
    if not meta:
        return entity

    normalized = dict(entity)
    normalized.pop("meta", None)

    repository = meta.get("repository", {})
    mapping = {
        "related_sale_and_locale": "relatedSaleAndLocale",
        "default_field": "default_field",
        "url_key": "url_key_field",
        "category": "category_field",
        "author": "author_field",
    }
    for meta_key, legacy_key in mapping.items():
        if meta_key in repository and legacy_key not in normalized:
            normalized[legacy_key] = repository[meta_key]

    if "string_field" in meta and "stringfield" not in normalized:
        normalized["stringfield"] = meta["string_field"]

    if meta.get("admin") is False:
        normalized["disable_admin"] = True

    return normalized


def validate_config(cfg: dict) -> list[str]:
    warnings: list[str] = []
    groups = cfg.get("groups", {})

    for group_name, group in groups.items():
        entities = group.get("entities", {})
        for entity_name, entity in entities.items():
            fields = entity.get("fields")
            if not fields:
                warnings.append(f"{group_name}/{entity_name}: missing fields")
                continue

            for field in fields:
                field_type = field.get("type")
                if field_type and field_type not in KNOWN_FIELD_TYPES:
                    warnings.append(
                        f"{group_name}/{entity_name}: unknown field type '{field_type}'"
                    )

                if field_type == "relation":
                    if not field.get("objectRelation"):
                        warnings.append(
                            f"{group_name}/{entity_name}.{field.get('name')}: "
                            "relation missing objectRelation"
                        )
                    if not field.get("typeRelation"):
                        warnings.append(
                            f"{group_name}/{entity_name}.{field.get('name')}: "
                            "relation missing typeRelation"
                        )

    return warnings


def load_config(config_path: Path | None = None) -> dict:
    path = config_path or DEFAULT_CONFIG
    cfg = _load_yaml_file(path)
    groups = cfg.get("groups", {})

    for group_name, group in groups.items():
        if "entities" in group:
            continue

        entities_dir = group.get("entities_dir")
        if not entities_dir:
            raise ValueError(
                f"Group '{group_name}' must define entities or entities_dir"
            )

        resolved_dir = (path.parent / entities_dir).resolve()
        raw_entities = _load_entities_from_dir(resolved_dir)
        group["entities"] = {
            name: _apply_meta_block(entity)
            for name, entity in raw_entities.items()
        }

    return cfg

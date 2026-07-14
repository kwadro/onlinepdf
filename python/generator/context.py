from __future__ import annotations

from dataclasses import dataclass, field
from typing import Iterator

from .writers import OUT

TYPE_MAP = {
    "integer": "int",
    "string": "string",
    "datetime": "\\DateTimeInterface",
    "datetime_immutable": "\\DateTimeImmutable",
    "date": "\\DateTimeInterface",
    "float": "float",
    "boolean": "bool",
    "text": "string",
    "choice": "string",
    "image": "string",
    "relation": "relation",
    "select": "string",
    "money": "string",
    "enum": "enum",
    "json": "array",
}

DOCTRINE_TYPE_MAP = {
    "integer": "integer",
    "string": "string",
    "datetime": "datetime",
    "datetime_immutable": "datetime_immutable",
    "date": "datetime",
    "float": "float",
    "boolean": "bool",
    "text": "text",
    "choice": "string",
    "image": "string",
    "relation": "relation",
    "select": "string",
    "money": "string",
    "enum": "enum",
    "json": "json",
}


def normalize_fields(fields: list[dict]) -> list[dict]:
    normalized = []
    for field_def in fields:
        enriched = dict(field_def)
        field_type = enriched.get("type")
        enriched["php_type"] = TYPE_MAP.get(field_type, field_type)
        enriched["doc_type"] = DOCTRINE_TYPE_MAP.get(field_type, field_type)
        normalized.append(enriched)
    return normalized


def flatten_entities(groups: dict) -> dict[str, dict]:
    all_entities: dict[str, dict] = {}
    for group in groups.values():
        for entity_name, entity in group.get("entities", {}).items():
            all_entities[entity_name] = entity
    return all_entities


@dataclass
class EntityRef:
    name: str
    entity: dict
    group_name: str
    group: dict


def resolve_group_paths(group: dict) -> dict[str, str]:
    output = group.get("output", {})
    package_root = OUT / output.get("package_root", ".")

    if output:
        return {
            "entity_dir": package_root / "src/Entity",
            "repository_dir": package_root / "src/Repository",
            "controller_dir": OUT / output.get("controller_root", "src/Controller/Admin"),
            "entity_namespace": output.get(
                "entity_namespace", "App\\Entity"
            ),
            "repository_namespace": output.get(
                "repository_namespace", "App\\Repository"
            ),
            "controller_namespace": output.get(
                "controller_namespace", "App\\Controller\\Admin"
            ),
            "enum_namespace": output.get(
                "enum_namespace", "App\\Enum"
            ),
            "model_namespace": output.get(
                "model_namespace", "App\\Model"
            ),
        }

    return {
        "entity_dir": OUT / "src/Entity",
        "repository_dir": OUT / "src/Repository",
        "controller_dir": OUT / "src/Controller/Admin",
        "entity_namespace": "App\\Entity",
        "repository_namespace": "App\\Repository",
        "controller_namespace": "App\\Controller\\Admin",
        "enum_namespace": "App\\Enum",
        "model_namespace": "App\\Model",
    }


@dataclass
class GeneratorContext:
    groups: dict
    all_entities: dict[str, dict] = field(default_factory=dict)
    generated_forms: set[str] = field(default_factory=set)

    @classmethod
    def from_config(cls, cfg: dict) -> "GeneratorContext":
        groups = cfg.get("groups", {})
        return cls(groups=groups, all_entities=flatten_entities(groups))

    def iter_entities(
        self,
        entity_filter: set[str] | None = None,
        group_filter: set[str] | None = None,
    ) -> Iterator[EntityRef]:
        for group_name, group in self.groups.items():
            if group_filter and group_name not in group_filter:
                continue

            for entity_name, entity in group.get("entities", {}).items():
                if entity_filter and entity_name not in entity_filter:
                    continue
                yield EntityRef(
                    name=entity_name,
                    entity=entity,
                    group_name=group_name,
                    group=group,
                )

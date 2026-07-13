from __future__ import annotations

from dataclasses import dataclass, field
from typing import Iterator

TYPE_MAP = {
    "integer": "int",
    "string": "string",
    "datetime": "\\DateTimeInterface",
    "date": "\\DateTimeInterface",
    "float": "float",
    "boolean": "bool",
    "text": "string",
    "choice": "string",
    "image": "string",
    "relation": "relation",
    "select": "string",
    "money": "string",
}

DOCTRINE_TYPE_MAP = {
    "integer": "integer",
    "string": "string",
    "datetime": "datetime",
    "date": "datetime",
    "float": "float",
    "boolean": "bool",
    "text": "text",
    "choice": "string",
    "image": "string",
    "relation": "relation",
    "select": "string",
    "money": "string",
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
                yield EntityRef(name=entity_name, entity=entity, group_name=group_name)

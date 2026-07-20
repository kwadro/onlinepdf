from __future__ import annotations

from .config import BASE
from .context import GeneratorContext, EntityRef, normalize_fields, resolve_group_paths
from .engine import render_template
from .writers import OUT, Writer

PER_ENTITY_TASKS = {"entity", "controller", "repository", "form_type", "import"}
ONCE_TASKS = {"dashboard", "export", "group_files"}
ALL_TASKS = PER_ENTITY_TASKS | ONCE_TASKS

ENTITY_TEMPLATES = {
    "subscription-plan": "entity-subscription-plan.php.j2",
    "subscription": "entity-subscription.php.j2",
}

REPOSITORY_TEMPLATES = {
    "subscription-plan": "repository-subscription-plan.php.j2",
    "subscription": "repository-subscription.php.j2",
    "mail-mailbox": "repository-mail-mailbox.php.j2",
    "mail-filter": "repository-mail-filter.php.j2",
    "mail-filter-group": "repository-mail-filter-group.php.j2",
    "mail-message": "repository-mail-message.php.j2",
}

CONTROLLER_TEMPLATES = {
    "subscription-plan": "controller-subscription-plan.php.j2",
    "subscription": "controller-subscription.php.j2",
}


def _relation_needs_form(field: dict) -> bool:
    if field.get("type") != "relation":
        return False

    type_relation = field.get("typeRelation")
    role_relation = field.get("roleRelation")

    if type_relation == "ManyToOne" and role_relation == "master":
        return True
    if type_relation == "ManyToMany" and role_relation == "master":
        return True
    return type_relation == "OneToMany"


def _template_context(entity_ref: EntityRef) -> dict:
    paths = resolve_group_paths(entity_ref.group)
    return {
        "name": entity_ref.name,
        **paths,
    }


def _entity_template_name(entity: dict) -> str:
    generator_template = entity.get("generator_template")
    if generator_template in ENTITY_TEMPLATES:
        return ENTITY_TEMPLATES[generator_template]
    return "entity.php.j2"


def _repository_template_name(entity: dict) -> str:
    repository = entity.get("repository", {})
    template = repository.get("template") or entity.get("generator_template")
    if template in REPOSITORY_TEMPLATES:
        return REPOSITORY_TEMPLATES[template]
    return "repository.php.j2"


def _controller_template_name(entity: dict) -> str:
    controller = entity.get("controller", {})
    template = controller.get("template") or entity.get("generator_template")
    if template in CONTROLLER_TEMPLATES:
        return CONTROLLER_TEMPLATES[template]
    return "controller.php.j2"


def generate_entity(writer: Writer, entity_ref: EntityRef) -> None:
    entity_name = entity_ref.name
    entity = entity_ref.entity
    paths = resolve_group_paths(entity_ref.group)
    fields = normalize_fields(entity["fields"])
    string_field = entity.get("stringfield", False)
    code = render_template(
        _entity_template_name(entity),
        fields=fields,
        stringField=string_field,
        **_template_context(entity_ref),
    )
    writer.write_file(paths["entity_dir"] / f"{entity_name}.php", code)


def generate_controller(writer: Writer, entity_ref: EntityRef) -> None:
    entity_name = entity_ref.name
    entity = entity_ref.entity
    if entity.get("disable_admin"):
        return

    paths = resolve_group_paths(entity_ref.group)
    template = _controller_template_name(entity)

    if template == "controller.php.j2":
        fields = normalize_fields(entity["fields"])
        code = render_template(template, name=entity_name, fields=fields, entity=entity)
    else:
        code = render_template(template, **_template_context(entity_ref))

    writer.write_file(
        paths["controller_dir"] / f"{entity_name}CrudController.php",
        code,
    )


def generate_form_types(
    writer: Writer,
    ctx: GeneratorContext,
    entity_ref: EntityRef,
) -> None:
    entity = entity_ref.entity
    if entity.get("disable_admin"):
        return

    fields = normalize_fields(entity["fields"])
    for field in fields:
        if not _relation_needs_form(field):
            continue

        class_name = field.get("objectRelation")
        if not class_name or class_name == "self":
            continue

        if class_name in ctx.generated_forms:
            continue

        class_entity = ctx.all_entities.get(class_name)
        if class_entity is None:
            print(
                f"Warning: related entity '{class_name}' not found, skipping form type"
            )
            continue

        class_fields = normalize_fields(class_entity.get("fields"))
        if not class_fields:
            continue

        code = render_template(
            "form-type.php.j2",
            name=class_name,
            fields=class_fields,
        )
        writer.write_file(OUT / f"src/Form/Type/{class_name}Type.php", code)
        ctx.generated_forms.add(class_name)


def generate_repository(writer: Writer, entity_ref: EntityRef) -> None:
    entity_name = entity_ref.name
    entity = entity_ref.entity
    paths = resolve_group_paths(entity_ref.group)
    template = _repository_template_name(entity)

    if template == "repository.php.j2":
        related = entity.get("relatedSaleAndLocale", False)
        default_field = entity.get("default_field", False)
        url_key_field = entity.get("url_key_field", False)
        category_field = entity.get("category_field", False)
        author_field = entity.get("author_field", False)
        code = render_template(
            template,
            name=entity_name,
            related=related,
            default=default_field,
            slug=url_key_field,
            category_field=category_field,
            author_field=author_field,
        )
    else:
        code = render_template(template, **_template_context(entity_ref))

    writer.write_file(paths["repository_dir"] / f"{entity_name}Repository.php", code)


def generate_import(writer: Writer, entity_ref: EntityRef) -> None:
    entity_name = entity_ref.name
    entity = entity_ref.entity
    if entity.get("disable_import"):
        return

    code = render_template("import/import-handler.php.j2", name=entity_name).strip()
    writer.write_file(OUT / f"src/Import/Handler/{entity_name}ImportHandler.php", code)


def generate_dashboard_link(writer: Writer, groups: dict) -> None:
    dashboard_groups = {
        name: group for name, group in groups.items() if not group.get("skip_dashboard")
    }
    if not dashboard_groups:
        return

    dashboard_path = OUT / "src/Controller/Admin/DashboardController.php"

    code = render_template("dashboard-use.php.j2", groups=dashboard_groups).strip()
    writer.insert_code_by_markers(
        file_path=dashboard_path,
        generated=code,
        start_marker="// @GENERATE USE START",
        end_marker="// @GENERATE USE FINISH",
    )

    code = render_template("dashboard-menu.php.j2", groups=dashboard_groups).strip()
    writer.insert_code_by_markers(
        file_path=dashboard_path,
        generated=code,
        start_marker="// @GENERATE MENU START",
        end_marker="// @GENERATE MENU FINISH",
    )

    uk_translate_path = OUT / "translations/messages.uk.yaml"
    code = render_template(
        "translate.yaml.j2",
        groups=dashboard_groups,
        lang_single="uk_single",
        lang="uk",
    ).strip()
    writer.insert_code_by_markers(
        file_path=uk_translate_path,
        generated=code,
        start_marker="#@GENERATE TRANSLATE START",
        end_marker="#@GENERATE TRANSLATE FINISH",
    )

    en_translate_path = OUT / "translations/messages.en.yaml"
    code = render_template(
        "translate.yaml.j2",
        groups=dashboard_groups,
        lang_single="en_single",
        lang="en",
    ).strip()
    writer.insert_code_by_markers(
        file_path=en_translate_path,
        generated=code,
        start_marker="#@GENERATE TRANSLATE START",
        end_marker="#@GENERATE TRANSLATE FINISH",
    )


def generate_export_script(writer: Writer, groups: dict) -> None:
    export_groups = {
        name: group for name, group in groups.items() if not group.get("skip_export")
    }
    code = render_template("export.sh.j2", groups=export_groups).strip()
    export_path = OUT / "bash/export-entity.sh"
    writer.write_file(export_path, code)

    if not writer.dry_run:
        with open(export_path, "a", encoding="utf-8") as handle:
            handle.write("\nphp bin/console app:export:csv User")

    code = render_template("import.sh.j2", groups=export_groups).strip()
#     writer.write_file(OUT / "bash/import-entity.sh", code)


def generate_group_files(
    writer: Writer,
    ctx: GeneratorContext,
    group_filter: set[str] | None = None,
) -> None:
    for group_name, group in ctx.groups.items():
        if group_filter and group_name not in group_filter:
            continue

        for file_spec in group.get("generate_files", []):
            template = file_spec.get("template")
            output_path = file_spec.get("path")
            if not template or not output_path:
                print(
                    f"Warning: group '{group_name}' generate_files entry "
                    "requires template and path"
                )
                continue

            print(f"Group: {group_name}, File: {output_path}")
            template_path = BASE / "templates" / template
            if file_spec.get("copy") or template.endswith(".php"):
                code = template_path.read_text(encoding="utf-8")
            else:
                code = render_template(template)
            writer.write_file(OUT / output_path, code)


def run_tasks(
    tasks: set[str],
    ctx: GeneratorContext,
    writer: Writer,
    entity_filter: set[str] | None = None,
    group_filter: set[str] | None = None,
) -> None:
    per_entity = tasks & PER_ENTITY_TASKS
    once = tasks & ONCE_TASKS

    for entity_ref in ctx.iter_entities(entity_filter, group_filter):
        print(f"Group: {entity_ref.group_name}, Entity: {entity_ref.name}")

        if "entity" in per_entity:
            generate_entity(writer, entity_ref)
        if "controller" in per_entity:
            generate_controller(writer, entity_ref)
        if "form_type" in per_entity:
            generate_form_types(writer, ctx, entity_ref)
        if "repository" in per_entity:
            generate_repository(writer, entity_ref)
        if "import" in per_entity:
            generate_import(writer, entity_ref)

    if "dashboard" in once:
        generate_dashboard_link(writer, ctx.groups)
    if "export" in once:
        generate_export_script(writer, ctx.groups)
    if "group_files" in once:
        generate_group_files(writer, ctx, group_filter)

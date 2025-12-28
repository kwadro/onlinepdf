
import pymysql
import yaml
import inflect

from jinja2 import Template
from pathlib import Path


p = inflect.engine()
# singularize для collection
def singularize(word):
    result = p.singular_noun(word)
    if result:
        return result
    return word

# capitalize першу букву
def capitalize(word):
    return word[:1].upper() + word[1:] if word else ''

BASE = Path(__file__).resolve().parent
OUT = BASE.parent

# add service function for getter and setter in template
def to_pascal_case(value):
    return ''.join(word.capitalize() for word in value.split('_'))
def get_connection():
    #   local
        DB_NAME="symfony"
        USER="root"
        PASS="root"
        HOST="127.0.0.1"
        PORT=3308
        #     live
        #     DB_NAME="kwadro_laravel"
        #     USER="kwadro_laravel"
        #     PASS="y22KN_t+u8"
        #     HOST="kwadro.mysql.tools"
        #     PORT="3308"
        conn = pymysql.connect(
            host=HOST,
            port=PORT,
            user=USER,
            password=PASS,
            database=DB_NAME,
            charset="utf8mb4",
            cursorclass=pymysql.cursors.DictCursor
        )
        return conn

def load_data_v2( siteId, localeId):
    conn = get_connection()
    cur = conn.cursor()
    localeIds = [1, 2]
    rows = []
    sql = """
    SELECT
        tr.locale_id,
        tr.*,
        e.favicon,
        e.logo
    FROM header_setting e
    JOIN header_translation tr
        ON tr.headersetting_id = e.id
    WHERE e.site_id = %s
    """
    cur.execute(sql, (siteId, ))
    results = cur.fetchall()
    rows = {}
    for row in results:
        rows[row['locale_id']] = dict(row)

        return rows.get(locale_id, {})

def load_data(entity):
    conn = get_connection()
    cur = conn.cursor()
    cur.execute("SELECT * FROM "+entity+" where id=1")
    rows = cur.fetchall()
    return rows

def render_template(template_file, **ctx):
    tpl = Template(open(BASE / "templates" / template_file).read())
    tpl.environment.filters['pascal'] = to_pascal_case
    tpl.environment.filters['singularize'] = singularize
    tpl.environment.filters['capitalize'] = capitalize
    return tpl.render(**ctx)

def write_file(path: Path, content: str):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content, encoding="utf-8")

def generate_template(file_name,setting):
    template_name = f"frontend/{file_name}.html.twig.j2"
    code = render_template(template_name, name=file_name,  setting=setting)
    write_file(OUT / f"templates/{file_name}.html.twig", code)

def insert_code_by_markers(file_path, generated, start_marker, end_marker):
    with open(file_path, "r", encoding="utf-8") as f:
        original = f.read()
    if start_marker not in original or end_marker not in original:
        raise ValueError(f"Markers not found in {file_path}")
    before = original.split(start_marker)[0]
    after = original.split(end_marker)[1]

    new_content = (
        before
        + start_marker
        + "\n"
        + generated
        + "\n"
        + end_marker
        + after
    )
    with open(file_path, "w", encoding="utf-8") as f:
        f.write(new_content)
    print(f"Updated: {file_path}")

def generate_dashboard_link(groups):
    ukTranslatePath = OUT / "translations/messages.uk.yaml"
    code = render_template("translate.yaml.j2", groups=groups, lang_single='uk_single', lang='uk').strip()
    insert_code_by_markers(
           file_path = ukTranslatePath,
           generated = code,
           start_marker = '#@GENERATE TRANSLATE START',
           end_marker = '#@GENERATE TRANSLATE FINISH',
        )

    enTranslatePath = OUT / "translations/messages.en.yaml"
    code = render_template("translate.yaml.j2", groups=groups, lang_single='en_single', lang='en').strip()
    insert_code_by_markers(
           file_path = enTranslatePath,
           generated = code,
           start_marker = '#@GENERATE TRANSLATE START',
           end_marker = '#@GENERATE TRANSLATE FINISH',
        )

def main():
    settings = load_data('setting')
    print(f"Settings: {settings}")
    for setting in settings:
        print("Recipe : " + setting['name'])
        generate_template('base-1',setting)

    headers = load_data('header')

    for header in headers:
        print("Header : " + header['name'])
        generate_template('header-1',header)

    footers = load_data('footer')
    print(f"Footer: {footers}")
    for footer in footers:
        print("Footer : " + footer['name'])
        generate_template('footer-1',footer)

if __name__ == "__main__":
    main()

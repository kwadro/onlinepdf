
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
#         DB_NAME="symfony"
#         USER="root"
#         PASS="root"
#         HOST="127.0.0.1"
#         PORT=3308
#             live
            DB_NAME="kwadro_laravel"
            USER="kwadro_laravel"
            PASS="y22KN_t+u8"
            HOST="kwadro.mysql.tools"
            PORT="3306"
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
        tr.*
    FROM mega_menu_setting e
    JOIN mega_menu_translation tr
        ON tr.megamenusetting_id = e.id
    WHERE e.site_id = %s and tr.status = %s and tr.locale_id = %s
    """
    cur.execute(sql, (siteId,'Yes', localeId))
    results = cur.fetchall()

    count = len(results)
    print(f"Number of results: {count}")
    return results

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

def generate_template(setting):
    file_name = setting['url']
    pathStr = f"{OUT}/templates/{file_name}"
    print(f"pathStr : {pathStr}")
    print(f"file_name : {file_name}")
    print(f"megamenutype_id : {setting['megamenutype_id']}")
    path = Path(pathStr)
    path.mkdir(parents=True, exist_ok=True)
    if (setting['megamenutype_id']==1):
        template_name = f"frontend/index-link.html.twig.j2"
        codeIndex = render_template(template_name, name=file_name,setting=setting)
        write_file(OUT / f"templates/{file_name}/index.html.twig", codeIndex)
        template_name = f"frontend/content-link.html.twig.j2"
        code = render_template(template_name,content = setting['content'])
        write_file(OUT / f"templates/{file_name}/{file_name}-content.html.twig", code)

    if (setting['megamenutype_id']==3):
        content = setting['content']
        entityClass = content.split('class="')[1].split('"')[0]
        print(f"entityClass: {entityClass}")
        template_name = f"frontend/index-form.html.twig.j2"
        codeIndex = render_template(template_name, name = file_name, entityClass = entityClass, setting = setting)
        write_file(OUT / f"templates/{file_name}/index.html.twig", codeIndex)
        template_name = f"frontend/content-form.html.twig.j2"
        code = render_template(template_name, entityClass = entityClass)
        write_file(OUT / f"templates/{file_name}/{file_name}-content.html.twig", code)


def main():
    settings = load_data_v2(1,1)
#     print(f"Settings: {settings}")
    for setting in settings:
            generate_template(setting)

if __name__ == "__main__":
    main()

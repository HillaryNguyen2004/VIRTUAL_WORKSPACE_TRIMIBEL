from __future__ import annotations

import re
from operator import itemgetter
from typing import Optional, Dict, Any

from .config import settings

from langchain.chains import create_sql_query_chain
from langchain_community.utilities import SQLDatabase
from langchain_community.tools.sql_database.tool import QuerySQLDataBaseTool
from langchain_core.output_parsers import StrOutputParser
from langchain_core.prompts import ChatPromptTemplate, PromptTemplate
from langchain_core.runnables import RunnableLambda, RunnablePassthrough
from langchain_ollama import ChatOllama

# ----------------------------
# DB connection
# ----------------------------
DB_URI = (
    f"{settings.db_connection}+mysqlconnector://"
    f"{settings.db_username}:{settings.db_password}"
    f"@{settings.db_host}:{settings.db_port}/{settings.db_database}"
)

db = SQLDatabase.from_uri(DB_URI)

query_tool = QuerySQLDataBaseTool(db=db)

llm = ChatOllama(
    model=getattr(settings, "ollama_model", "llama3.2-vision"),
    temperature=0,
    base_url=getattr(settings, "ollama_base_url", "http://localhost:11434"),
    validate_model_on_init=True,
)

# ----------------------------
# Helpers
# ----------------------------
def get_schema(_: Dict[str, Any]) -> str:
    """Return database schema info for the LLM."""
    return db.get_table_info()

_SQL_CODE_FENCE_RE = re.compile(r"^\s*```(?:sql)?\s*|\s*```\s*$", re.IGNORECASE)

def normalize_and_validate_sql(raw: str) -> str:
    """
    Normalize model output into a single safe SELECT/CTE statement.

    - Strips markdown fences
    - Strips leading/trailing whitespace and trailing semicolon
    - Blocks non-SELECT statements
    - Blocks multiple statements (best-effort)
    """
    if raw is None:
        raise ValueError("Empty SQL output.")

    sql = _SQL_CODE_FENCE_RE.sub("", raw).strip()
    sql = sql.rstrip(";").strip()

    # Best-effort single-statement guard
    if ";" in sql:
        raise ValueError("Multiple SQL statements detected; refusing to execute.")

    # Allow SELECT or CTE (WITH ... SELECT)
    lowered = sql.lstrip().lower()
    if not (lowered.startswith("select") or lowered.startswith("with")):
        raise ValueError("Only SELECT/CTE queries are allowed.")

    # Hard block common DDL/DML keywords anywhere (best-effort)
    banned = [
        "insert", "update", "delete", "drop", "truncate", "alter",
        "create", "replace", "grant", "revoke", "call", "load", "outfile",
    ]
    if any(re.search(rf"\b{kw}\b", lowered) for kw in banned):
        raise ValueError("Potentially unsafe SQL detected; refusing to execute.")

    return sql

def build_sql_chain() -> Any:
    """
    NL -> SQL chain using create_sql_query_chain + a strict prompt.
    This follows the Roelfs pattern: custom PromptTemplate must include:
    {input}, {table_info}, {top_k}. :contentReference[oaicite:2]{index=2}
    """
    sql_template = f"""
You are an expert MySQL assistant.

Task:
- Generate ONE syntactically correct MySQL query that answers the user's question.

Rules (MUST follow):
- Use ONLY SELECT or WITH ... SELECT (no data modification).
- Use only table/column names that exist in the provided schema.
- Do NOT use SELECT *; select only needed columns.
- If the user asks for examples/rows, return at most {{top_k}} rows (use LIMIT).
- Return ONLY the SQL query (no markdown, no commentary).

Schema:
{{table_info}}

{{input}}
""".strip()

    sql_prompt = PromptTemplate.from_template(sql_template)

    # Optional stop tokens help keep output “SQL-only” with some models.
    sql_llm = llm.bind(stop=["\nSQLResult:", "\nSQL Result:", "\nAnswer:"])

    return (
        create_sql_query_chain(sql_llm, db, sql_prompt)
        | RunnableLambda(normalize_and_validate_sql)
    )


sql_chain = build_sql_chain()

# ----------------------------
# Chain 2: SQL -> natural-language answer
# ----------------------------
answer_prompt = ChatPromptTemplate.from_template(
    """
You are Bot Bot, a friendly and concise assistant.

Formatting rules (MUST follow):
- Output MUST be valid Markdown.
- If you output a list, use hyphen bullets only: "- " (dash + space). Do NOT use "*" bullets.
- To emphasize text, use Markdown bold only: **like this**. Do NOT use HTML tags for bold.
- Keep line breaks as written. Do not wrap the entire answer in a code block.

You receive:
- The database schema,
- The user's question,
- The SQL query that was executed,
- The raw SQL response from the database.

Your task:
- Explain the answer to the user in natural language.
- Be accurate and do NOT invent data.
- If the SQL response is empty or clearly does not answer the question,
  say you don't know or that there is no matching data.
- Do NOT mention SQL, tables, or the database in your final answer.

Always answer in {target_lang}.

Schema:
{schema}

User question:
{question}

Executed query:
{query}

Raw response:
{response}

Answer:
""".strip()
)

full_chain = (
    RunnablePassthrough.assign(query=sql_chain)
    .assign(
        schema=get_schema,
        response=itemgetter("query") | query_tool,
    )
    | answer_prompt
    | llm
    | StrOutputParser()
)

def answer_from_db(
    question: str,
    target_lang: str = "en",
    user_id: Optional[int] = None,
) -> str:
    """
    Uses LangChain's built-in SQL query chain + query execution tool,
    then renders a final natural-language answer (Roelfs-style). :contentReference[oaicite:3]{index=3}
    """
    user_hint = ""
    if user_id is not None:
        user_hint = (
            f"The current authenticated user has id = {user_id} in the database. "
            f"When the question is about 'my' data (my tasks, my requests, my check-ins, etc.), "
            f"you MUST filter by this user_id in SQL queries.\n\n"
        )

    schema_description = f"""
About user:
- Users who have the same leader_id with current authenticated user with id = {user_id}.

About check-ins:
- Users who check in after start time of company hour will decide as late.

About projects:
- Project is like a parent of tasks.
- Only admin role can create project and then admin or staff role will create tasks from that project.

About tasks:
- Give some advises to solve the task from the questions (if you can).
- Task with the same project_id will be in the same project

About task_user:
- Each task id will map with each user id
- Using {user_id} to find and retrieve task
""".strip()

    full_question = f"""
{user_hint}
{schema_description}

Answer in {target_lang}.

User question: {question}
""".strip()

    try:
        answer_text: str = full_chain.invoke(
            {"question": full_question, "target_lang": target_lang}
        )
        return answer_text.strip()
    except Exception as e:
        msg = str(e)
        if "ResourceExhausted" in msg or "429" in msg:
            return (
                "I’m sorry, but I can’t access the database right now. Please try again later or contact the administrator."
            )
        return (
            "I’m sorry, but I couldn’t retrieve your data from the database due to an internal error. Please contact support so we can investigate."
        )

from __future__ import annotations
from typing import Optional, Dict, Any

from .config import settings

from langchain_community.utilities import SQLDatabase
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.runnables import RunnablePassthrough
from langchain_core.output_parsers import StrOutputParser
from langchain_google_genai import ChatGoogleGenerativeAI

# DB connection
DB_URI = (
    f"{settings.db_connection}+mysqlconnector://"
    f"{settings.db_username}:{settings.db_password}"
    f"@{settings.db_host}:{settings.db_port}/{settings.db_database}"
)

db = SQLDatabase.from_uri(DB_URI)

llm = ChatGoogleGenerativeAI(
    model=settings.gen_model,
    temperature=0,
)

# Helpers for schema + query execution
def get_schema(_: Dict[str, Any]) -> str:
    """Return database schema info for the LLM."""
    return db.get_table_info()

def run_query(query: str) -> str:
    """Execute SQL query on the DB and return the raw result as string."""
    return db.run(query)

# Chain 1: natural language -> SQL (sql_chain)
sql_prompt = ChatPromptTemplate.from_template(
    """
        You are an expert MySQL assistant.

        Based on the table schema below, write a SINGLE SQL SELECT query
        that answers the user's question.

        Rules:
        - Use ONLY SELECT (no INSERT, UPDATE, DELETE, DROP, TRUNCATE, or other modifications).
        - Use correct table and column names from the schema.
        - Do NOT add explanations, comments, markdown, or natural language.
        - Return only the SQL query.

        Schema:
        {schema}

        User question:
        {question}

        SQL Query:
    """.strip()
)

sql_chain = (
    RunnablePassthrough.assign(schema=get_schema)
    | sql_prompt
    # Gemini works fine with .bind() for stop tokens
    | llm.bind(stop=["\nSQLResult:", "\nSQL Result:", "\nAnswer:"])
    | StrOutputParser()  # final output: plain SQL string
)

# Chain 2: SQL -> natural-language answer (full_chain)
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

        SQL query:
        {query}

        SQL response:
        {response}

        Answer:
    """.strip()
)

full_chain = (
    # First step: get SQL query from sql_chain
    RunnablePassthrough.assign(query=sql_chain)
    # Second: attach schema and run the query
    .assign(
        schema=get_schema,
        response=lambda vars: run_query(vars["query"]),
    )
    # Third: turn everything into a natural-language answer
    | answer_prompt
    | llm
    | StrOutputParser()   # final output: plain answer text
)

def answer_from_db(
    question: str,
    target_lang: str = "en",
    user_id: Optional[int] = None,
) -> str:
    """
    Use Gemini + LangChain chains (Alejandro-style) to:
      1) Generate SQL from natural language,
      2) Run it on MySQL,
      3) Turn the result into a friendly answer.

    `user_id` is used as a hint so the model knows how to filter "my" data.
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
        - Ony admin role can create project and then admin or staff role will create tasks from that project.
        
        About tasks:
        - Give some advises to solve the task from the questions (if you can).
        - Task with the same project_id will be in the same project
        
        About task_user:
        - Each task id will map with each user id
        - Using {user_id} to find and retrieve task
    """

    # Inject user hint + language into the question passed to the chain
    full_question = f"""
        {user_hint}
        
        {schema_description}
                
        Answer in {target_lang}.
        
        User question: {question}
    """

    try:
        answer_text: str = full_chain.invoke(
            {"question": full_question, "target_lang": target_lang}
        )
        return answer_text.strip()
    except Exception as e:
        msg = str(e)
        # Gemini free-tier / quota / 429 handling
        if "ResourceExhausted" in msg or "429" in msg:
            return (
                "I’m sorry, but I can’t access the database right now. Please try again later or contact the administrator."
            )
        return (
            "I’m sorry, but I couldn’t retrieve your data from the database due to an internal error. Please contact support so we can investigate."
        )

import os, httpx, chromadb
from langfuse import Langfuse
from src.rag.config import settings

# Silence Chroma telemetry noise
os.environ["ANONYMIZED_TELEMETRY"] = "False"

# Langfuse
langfuse = Langfuse(
    public_key=settings.langfuse_public_key,
    secret_key=settings.langfuse_secret_key,
    base_url=settings.langfuse_base_url,
    httpx_client=httpx.Client(timeout=60),
)
langfuse.auth_check()

DATASET_NAME = settings.dataset_name
langfuse.create_dataset(name=DATASET_NAME)

# Chroma
DB_PATH = settings.chroma_dir
COLLECTION_NAME = settings.collection
TOP_K = settings.top_k

client = chromadb.PersistentClient(path=DB_PATH)
col = client.get_collection(COLLECTION_NAME)

# More questions (we will take only 10)
QUESTIONS = [
    "What are the three main roles in the AI-powered Virtual Office system?",
    "What features are included in the application?",
    "How does a regular user check attendance?",
    "What does the Team Members card show?",
    "What does the Assigned Tasks card do?",
    "What controls are available at the top of the User Management screen?",
    "What is the purpose of the search bar in User Management?",
    "How do you filter users by role?",
    "How do you export the user list?",
    "What communication features does the app provide?",
    "Does the app include real-time notifications and profile settings?",
    "What is the dashboard for regular users focused on?",
    "What should a user do to check in at the start of work?",
    "What should a user do to check out when finishing work?",
    "What happens if there are no team members assigned?",
    "How do you open the full-screen Assigned Tasks view?",
    "What can an Admin do in the system (high level)?",
    "What can a Staff role do (high level)?",
    "What is an AI Chat Bot used for in this app?",
    "Where can a user find task and project views?",
    "What is included in campaign and email template management?",
    "What is attendance and check-in tracking used for?",
    "How do dashboards differ across roles?",
    "What is shown on the Regular User dashboard besides attendance?",
    "What are typical steps to access check attendance on the dashboard?",
    "What is the role filter dropdown used for?",
    "What sorting control is available in User Management?",
    "What do filter and reset icons do in User Management?",
    "What is the Export to Excel button used for?",
    "How does the guideline recommend using this document?",
]
QUESTIONS = QUESTIONS[:10]  # <= limit to 10

# Facts for the first 10 questions in QUESTIONS[:10]
FACTS = {
    "What are the three main roles in the AI-powered Virtual Office system?": [
        "Admin", "Staff", "User"
    ],

    "What features are included in the application?": [
        "Dashboards", "Task and project", "User and permission management",
        "Attendance", "Chat Box", "Video Chat"
    ],

    "How does a regular user check attendance?": [
        "Check attendance", "Dashboard", "Check In", "Check Out"
    ],

    "What does the Team Members card show?": [
        "Team Members", "same team", "no team members"
    ],

    "What does the Assigned Tasks card do?": [
        "Assigned Tasks", "assigned", "expand icon", "full-screen"
    ],

    "What controls are available at the top of the User Management screen?": [
        "Search by username or email", "All Roles", "A → Z", "Export to Excel"
    ],

    "What is the purpose of the search bar in User Management?": [
        "Search by username or email"
    ],

    "How do you filter users by role?": [
        "Admin", "Staff", "User"
    ],

    "How do you export the user list?": [
        "Export to Excel"
    ],

    "What communication features does the app provide?": [
        "Chat Box", "Video Chat"
    ],
}

# Fetch all chunks once (faster)
res_all = col.get(include=["documents", "metadatas"])
ROWS = list(zip(res_all["ids"], res_all["documents"], res_all["metadatas"]))

def retrieve_context_no_embedding(question: str, top_k: int = 4):
    q = question.lower()

    def score(doc: str) -> int:
        d = (doc or "").lower()
        return sum(1 for w in q.split() if len(w) > 3 and w in d)

    ranked = sorted(ROWS, key=lambda r: score(r[1]), reverse=True)
    ranked = [r for r in ranked if score(r[1]) > 0] or ROWS

    ctx = []
    for cid, doc, meta in ranked[:top_k]:
        ctx.append({"id": cid, "source": meta.get("source", ""), "text": doc})
    return ctx

for i, q in enumerate(QUESTIONS, start=1):
    context = retrieve_context_no_embedding(q, top_k=TOP_K)

    langfuse.create_dataset_item(
        dataset_name=DATASET_NAME,
        input={
            "message": q,
            "k": TOP_K,
            "context": context,
        },
        expected_output={
            "response_facts": FACTS.get(q, []),
            "trajectory": [c["id"] for c in context],
        },
        metadata={"case_id": f"avo_{i:03d}", "collection": COLLECTION_NAME, "top_k": TOP_K},
    )

langfuse.flush()
print("Seeded dataset:", DATASET_NAME, "with", len(QUESTIONS), "items")
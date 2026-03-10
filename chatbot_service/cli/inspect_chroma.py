import os
import chromadb
from pprint import pprint
from dotenv import load_dotenv

load_dotenv()

os.getenv("ANONYMIZED_TELEMETRY", "False")

DB_PATH = "var/chroma_db"  # your repo shows var/chroma_db/
client = chromadb.PersistentClient(path=DB_PATH)

# 1) list collections
cols = client.list_collections()
print("Collections:", [c.name for c in cols])

# 2) pick one collection name (replace with the correct one from the printed list)
COLLECTION_NAME = cols[0].name
col = client.get_collection(COLLECTION_NAME)

print("Chunk count:", col.count())

# 3) preview some chunks
res = col.get(limit=5, include=["documents", "metadatas"])
for cid, doc, meta in zip(res["ids"], res["documents"], res["metadatas"]):
    print("\n---", cid, "---")
    pprint(meta)
    print(doc[:400])
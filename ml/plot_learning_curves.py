"""
Generate learning curve figure for thesis (Figure 4.Z)
Uses the canonical model seed (seed 45) for consistency
"""
import pandas as pd
import matplotlib.pyplot as plt
import os

os.makedirs("figures", exist_ok=True)

SEED = 45  # canonical model — must match the seed referenced in thesis tables

h = pd.read_csv(f"runs/seed_{SEED}/history.csv")

fig, axes = plt.subplots(1, 2, figsize=(11, 4))

# Loss
axes[0].plot(h["epoch"], h["loss"],     label="Training",   linewidth=1.8)
axes[0].plot(h["epoch"], h["val_loss"], label="Validation", linewidth=1.8)
axes[0].set_xlabel("Epoch")
axes[0].set_ylabel("Sparse categorical cross-entropy loss")
axes[0].set_title("(a) Loss")
axes[0].legend(loc="upper right")
axes[0].grid(True, alpha=0.3)

# Accuracy
axes[1].plot(h["epoch"], h["accuracy"],     label="Training",   linewidth=1.8)
axes[1].plot(h["epoch"], h["val_accuracy"], label="Validation", linewidth=1.8)
axes[1].set_xlabel("Epoch")
axes[1].set_ylabel("Accuracy")
axes[1].set_title("(b) Accuracy")
axes[1].legend(loc="lower right")
axes[1].grid(True, alpha=0.3)

# Mark the best epoch (where val_loss was minimised — early stopping picked this)
best_epoch = h["val_loss"].idxmin()
for ax in axes:
    ax.axvline(best_epoch, color="grey", linestyle="--", linewidth=1, alpha=0.6)

plt.tight_layout()
plt.savefig(f"figures/learning_curves_seed{SEED}.png", dpi=150, bbox_inches="tight")
plt.show()

print(f"Saved → figures/learning_curves_seed{SEED}.png")
print(f"Best val_loss at epoch {best_epoch} (val_loss = {h['val_loss'].min():.4f})")

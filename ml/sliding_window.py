"""
Generate a sliding-window diagram for the thesis (Figure 4.Y).

Output: figures/sliding_window.png

Visualises:
  - 14-day input window ending at day t
  - Target on day t+1
  - Two consecutive sliding positions to show how the window advances
"""

import os
import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
from matplotlib.patches import FancyArrowPatch

os.makedirs("figures", exist_ok=True)

# ── Colours (greyscale-friendly, distinct in B/W print) ──
COLOR_FEATURE  = "#4A90E2"   # blue — feature window
COLOR_TARGET   = "#E27D60"   # orange — prediction target
COLOR_UNUSED   = "#E8E8E8"   # light grey — context / not in window
COLOR_TEXT     = "#222222"

LOOKBACK = 14

# ──────────────────────────────────────────────────────────────────
# Layout parameters
# ──────────────────────────────────────────────────────────────────
N_DAYS_SHOWN = 18      # show enough days to make the slide visible
DAY_WIDTH    = 0.9
DAY_HEIGHT   = 0.55
ROW_GAP      = 1.5

# X positions for each day index (day 0 = leftmost)
def day_x(i):
    return i * DAY_WIDTH

# Two example windows to illustrate the slide.
# Each row uses its OWN local "t" — the last day of that row's window.
# So labels in each row read d_{t-13} ... d_t -> d_{t+1} relative to that row.
positions = [
    {"y": 1.5,  "win_start": 0, "win_end": 13, "target": 14, "label": "Position $A$"},
    {"y": 0.0,  "win_start": 1, "win_end": 14, "target": 15, "label": "Position $B$\n(one day later)"},
]

# ──────────────────────────────────────────────────────────────────
# Build figure
# ──────────────────────────────────────────────────────────────────
fig, ax = plt.subplots(figsize=(13, 4.2))

for pos in positions:
    y = pos["y"]

    for i in range(N_DAYS_SHOWN):
        x = day_x(i)

        # Decide colour based on role
        if pos["win_start"] <= i <= pos["win_end"]:
            color = COLOR_FEATURE
            text_color = "white"
            edge = "none"
        elif i == pos["target"]:
            color = COLOR_TARGET
            text_color = "white"
            edge = "none"
        else:
            color = COLOR_UNUSED
            text_color = "#888888"
            edge = "none"

        # Draw the day cell
        rect = mpatches.FancyBboxPatch(
            (x, y), DAY_WIDTH * 0.92, DAY_HEIGHT,
            boxstyle="round,pad=0.02,rounding_size=0.05",
            facecolor=color, edgecolor=edge, linewidth=0,
        )
        ax.add_patch(rect)

        # Day label inside the cell — relative to THIS row's local "t" = win_end
        if pos["win_start"] <= i <= pos["win_end"]:
            offset = pos["win_end"] - i
            if offset == 0:
                label_str = "$d_t$"
            else:
                label_str = f"$d_{{t-{offset}}}$"
        elif i == pos["target"]:
            label_str = "$d_{t+1}$"
        else:
            label_str = ""

        ax.text(x + DAY_WIDTH * 0.46, y + DAY_HEIGHT / 2,
                label_str, ha="center", va="center",
                color=text_color, fontsize=9, fontweight="bold")

    # ── Curly bracket under feature window ──
    win_start_x = day_x(pos["win_start"])
    win_end_x   = day_x(pos["win_end"]) + DAY_WIDTH * 0.92
    ax.annotate("",
                xy=(win_start_x, y - 0.05),
                xytext=(win_end_x, y - 0.05),
                arrowprops=dict(arrowstyle='-', color=COLOR_FEATURE, lw=1.5))
    ax.text((win_start_x + win_end_x) / 2, y - 0.25,
            "Input window (14 days)",
            ha="center", va="top", color=COLOR_FEATURE,
            fontsize=10, fontweight="bold")

    # ── Arrow from window → target ──
    target_x = day_x(pos["target"]) + DAY_WIDTH * 0.46
    arrow = FancyArrowPatch(
        (win_end_x + 0.05, y + DAY_HEIGHT / 2),
        (target_x - DAY_WIDTH * 0.46, y + DAY_HEIGHT / 2),
        arrowstyle='->', mutation_scale=15,
        color=COLOR_TEXT, lw=1.5,
    )
    ax.add_patch(arrow)
    ax.text((win_end_x + target_x) / 2, y + DAY_HEIGHT / 2 + 0.35,
            "predict", ha="center", va="bottom",
            color=COLOR_TEXT, fontsize=9, style="italic")

    # ── Position label on the left ──
    ax.text(-0.7, y + DAY_HEIGHT / 2, pos["label"],
            ha="right", va="center", color=COLOR_TEXT,
            fontsize=11, fontweight="bold")

# ── Connecting note: Position A → Position B (the "slide") ──
ax.annotate(
    "",
    xy=(day_x(0) + DAY_WIDTH * 0.46, positions[1]["y"] + DAY_HEIGHT + 0.1),
    xytext=(day_x(0) + DAY_WIDTH * 0.46, positions[0]["y"] - 0.05),
    arrowprops=dict(arrowstyle='->', color="#999999", lw=1.2,
                    connectionstyle="arc3,rad=-0.15"),
)
ax.text(day_x(-0.3), (positions[0]["y"] + positions[1]["y"]) / 2 + DAY_HEIGHT / 2,
        "slide forward\nby 1 day",
        ha="right", va="center", color="#666666", fontsize=8.5, style="italic")

# ── Legend ──
legend_handles = [
    mpatches.Patch(facecolor=COLOR_FEATURE, label="Input features (14 days)"),
    mpatches.Patch(facecolor=COLOR_TARGET,  label="Prediction target (next day)"),
    mpatches.Patch(facecolor=COLOR_UNUSED,  label="Other days (not in this window)"),
]
ax.legend(handles=legend_handles, loc="upper center",
          bbox_to_anchor=(0.5, -0.05), ncol=3,
          frameon=False, fontsize=9.5)

# ── Aesthetics ──
ax.set_xlim(-1.2, day_x(N_DAYS_SHOWN) + 0.2)
ax.set_ylim(-0.7, positions[0]["y"] + DAY_HEIGHT + 0.5)
ax.set_aspect("equal")
ax.axis("off")

plt.tight_layout()
plt.savefig("figures/sliding_window.png", dpi=200, bbox_inches="tight",
            facecolor="white")
# plt.savefig("figures/sliding_window.pdf",          bbox_inches="tight",
#             facecolor="white")  # vector version for thesis if you want it
plt.show()

print("✅ Saved → figures/sliding_window.png  and  figures/sliding_window.pdf")

from pathlib import Path
import pandas as pd
import numpy as np
import matplotlib.pyplot as plt


BASE_DIR = Path(__file__).resolve().parents[1]

DATASET_PATH = BASE_DIR / "datasets" / "combined_ux_friction_dataset_v2_full_unique.csv"
FIGURES_DIR = BASE_DIR / "outputs" / "figures"

NUMERIC_COLUMNS = [
    "completion_time",
    "click_count",
    "scroll_count",
    "keyboard_count",
    "retry_count",
    "error_count",
    "failed_clicks",
    "feedback_delay",
    "task_completed",
    "screenshot_count",
    "error_message_clarity",
]


def save_bar_chart(series: pd.Series, title: str, xlabel: str, ylabel: str, output_path: Path) -> None:
    plt.figure(figsize=(10, 6))
    series.plot(kind="bar")
    plt.title(title)
    plt.xlabel(xlabel)
    plt.ylabel(ylabel)
    plt.xticks(rotation=45, ha="right")
    plt.tight_layout()
    plt.savefig(output_path, dpi=300)
    plt.close()


def save_histogram(df: pd.DataFrame, column: str, output_path: Path) -> None:
    plt.figure(figsize=(10, 6))
    df[column].dropna().plot(kind="hist", bins=40)
    plt.title(f"Distribution of {column}")
    plt.xlabel(column)
    plt.ylabel("Frequency")
    plt.tight_layout()
    plt.savefig(output_path, dpi=300)
    plt.close()


def save_correlation_matrix(df: pd.DataFrame, output_path: Path) -> None:
    corr = df.corr(numeric_only=True)

    plt.figure(figsize=(12, 10))
    plt.imshow(corr, aspect="auto")
    plt.colorbar()
    plt.title("Numeric Feature Correlation Matrix")

    tick_positions = np.arange(len(corr.columns))
    plt.xticks(tick_positions, corr.columns, rotation=90)
    plt.yticks(tick_positions, corr.columns)

    for i in range(len(corr.columns)):
        for j in range(len(corr.columns)):
            value = corr.iloc[i, j]
            plt.text(j, i, f"{value:.2f}", ha="center", va="center", fontsize=7)

    plt.tight_layout()
    plt.savefig(output_path, dpi=300)
    plt.close()


def main() -> None:
    FIGURES_DIR.mkdir(parents=True, exist_ok=True)

    if not DATASET_PATH.exists():
        raise FileNotFoundError(f"Dataset not found: {DATASET_PATH}")

    df = pd.read_csv(DATASET_PATH)

    for col in NUMERIC_COLUMNS:
        df[col] = pd.to_numeric(df[col], errors="coerce")

    class_counts = df["friction_level"].value_counts()
    source_counts = df["source_dataset"].value_counts()

    save_bar_chart(
        class_counts,
        "Friction Level Distribution",
        "Friction Level",
        "Row Count",
        FIGURES_DIR / "class_distribution.png",
    )

    save_bar_chart(
        source_counts,
        "Source Dataset Distribution",
        "Source Dataset",
        "Row Count",
        FIGURES_DIR / "source_distribution.png",
    )

    for col in NUMERIC_COLUMNS:
        save_histogram(
            df,
            col,
            FIGURES_DIR / f"{col}_distribution.png",
        )

    save_correlation_matrix(
        df[NUMERIC_COLUMNS],
        FIGURES_DIR / "correlation_matrix.png",
    )

    print("=" * 80)
    print("GAgent Phase 4 EDA Completed")
    print("=" * 80)
    print(f"Dataset path: {DATASET_PATH}")
    print(f"Figures saved to: {FIGURES_DIR}")
    print("Generated charts:")
    print("- class_distribution.png")
    print("- source_distribution.png")
    for col in NUMERIC_COLUMNS:
        print(f"- {col}_distribution.png")
    print("- correlation_matrix.png")


if __name__ == "__main__":
    main()
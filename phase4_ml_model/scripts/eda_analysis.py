from pathlib import Path
import pandas as pd
import numpy as np
import matplotlib.pyplot as plt

ROOT_DIR = Path(__file__).resolve().parents[1]
DATASET_PATH = ROOT_DIR / "datasets" / "gagent_full_ux_friction_dataset.csv"

FIGURES_DIR = ROOT_DIR / "outputs" / "figures"
REPORTS_DIR = ROOT_DIR / "outputs" / "reports"

FIGURES_DIR.mkdir(parents=True, exist_ok=True)
REPORTS_DIR.mkdir(parents=True, exist_ok=True)

TARGET_COLUMN = "friction_level"

KEY_NUMERIC_FEATURES = [
    "completion_time",
    "click_count",
    "scroll_count",
    "keyboard_count",
    "retry_count",
    "error_count",
    "failed_clicks",
    "unnecessary_clicks",
    "path_deviation_score",
    "page_load_time_ms",
    "dom_content_loaded_ms",
    "time_to_first_byte_ms",
    "feedback_delay_ms",
    "interaction_to_next_paint_ms",
    "cumulative_layout_shift",
    "error_message_clarity",
    "popup_detected",
    "cookie_banner_detected",
    "overlay_blocks_cta",
]


def save_bar_chart(series: pd.Series, title: str, xlabel: str, ylabel: str, filename: str):
    plt.figure(figsize=(9, 5))
    series.plot(kind="bar")
    plt.title(title)
    plt.xlabel(xlabel)
    plt.ylabel(ylabel)
    plt.xticks(rotation=30, ha="right")
    plt.tight_layout()
    plt.savefig(FIGURES_DIR / filename, dpi=200)
    plt.close()


def save_correlation_heatmap(df: pd.DataFrame):
    numeric_df = df.select_dtypes(include=[np.number])

    if numeric_df.empty:
        return

    corr = numeric_df.corr()

    plt.figure(figsize=(14, 10))
    plt.imshow(corr, aspect="auto")
    plt.colorbar()
    plt.xticks(range(len(corr.columns)), corr.columns, rotation=90)
    plt.yticks(range(len(corr.columns)), corr.columns)
    plt.title("Correlation Heatmap for Numeric Features")
    plt.tight_layout()
    plt.savefig(FIGURES_DIR / "correlation_heatmap.png", dpi=200)
    plt.close()


def save_feature_histograms(df: pd.DataFrame):
    for col in KEY_NUMERIC_FEATURES:
        if col not in df.columns:
            continue

        plt.figure(figsize=(8, 5))
        df[col].hist(bins=30)
        plt.title(f"Distribution of {col}")
        plt.xlabel(col)
        plt.ylabel("Frequency")
        plt.tight_layout()
        plt.savefig(FIGURES_DIR / f"distribution_{col}.png", dpi=200)
        plt.close()


def save_boxplots_by_label(df: pd.DataFrame):
    for col in KEY_NUMERIC_FEATURES:
        if col not in df.columns or TARGET_COLUMN not in df.columns:
            continue

        labels = ["Low", "Medium", "High"]
        values = [df[df[TARGET_COLUMN] == label][col].dropna() for label in labels]

        plt.figure(figsize=(8, 5))
        plt.boxplot(values, labels=labels)
        plt.title(f"{col} by Friction Level")
        plt.xlabel("Friction Level")
        plt.ylabel(col)
        plt.tight_layout()
        plt.savefig(FIGURES_DIR / f"boxplot_{col}_by_friction_level.png", dpi=200)
        plt.close()


def main():
    if not DATASET_PATH.exists():
        raise FileNotFoundError(f"Dataset not found: {DATASET_PATH}")

    df = pd.read_csv(DATASET_PATH)

    if TARGET_COLUMN in df.columns:
        save_bar_chart(
            df[TARGET_COLUMN].value_counts(),
            "Class Distribution",
            "Friction Level",
            "Row Count",
            "class_distribution.png",
        )

    if "flow_type" in df.columns:
        save_bar_chart(
            df["flow_type"].value_counts(),
            "Flow Distribution",
            "Flow Type",
            "Row Count",
            "flow_distribution.png",
        )

    if "viewport_type" in df.columns:
        save_bar_chart(
            df["viewport_type"].value_counts(),
            "Viewport Distribution",
            "Viewport Type",
            "Row Count",
            "viewport_distribution.png",
        )

    if "scenario_type" in df.columns:
        save_bar_chart(
            df["scenario_type"].value_counts(),
            "Scenario Distribution",
            "Scenario Type",
            "Row Count",
            "scenario_distribution.png",
        )

    save_correlation_heatmap(df)
    save_feature_histograms(df)
    save_boxplots_by_label(df)

    report_lines = [
        "# EDA Summary",
        "",
        f"Dataset path: `{DATASET_PATH}`",
        f"Rows: {len(df):,}",
        f"Columns: {len(df.columns):,}",
        "",
        "## Generated Figures",
        "",
        "- class_distribution.png",
        "- flow_distribution.png",
        "- viewport_distribution.png",
        "- scenario_distribution.png",
        "- correlation_heatmap.png",
        "- distribution charts for key numeric features",
        "- boxplots by friction level for key numeric features",
        "",
        "## Interpretation",
        "",
        "The class distribution should be checked to ensure that Low, Medium, and High labels are reasonably balanced.",
        "Flow and viewport charts confirm whether the dataset covers different task types and device layouts.",
        "The correlation heatmap helps identify features that may overlap strongly.",
        "Boxplots show whether UX friction metrics increase as friction level becomes higher.",
        "",
        "Important: scenario_type is useful for EDA only and should not be used as an ML feature because it can reveal the designed friction condition.",
    ]

    output_path = REPORTS_DIR / "eda_summary.md"
    output_path.write_text("\n".join(report_lines), encoding="utf-8")

    print("EDA completed.")
    print(f"Figures saved to: {FIGURES_DIR}")
    print(f"EDA report saved to: {output_path}")


if __name__ == "__main__":
    main()
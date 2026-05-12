import pandas as pd
import numpy as np
from pathlib import Path

# ==============================
# PATH SETTINGS
# ==============================

PROJECT_ROOT = Path(__file__).resolve().parents[1]

DATASET_ROOT = Path(r"D:\FYP\Dataset")

BEHAVIOURAL_ROOT = DATASET_ROOT / "Behavioral interaction datasets"

OUTPUT_DIR = PROJECT_ROOT / "outputs" / "processed"
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

TARGET_FINAL_ROWS = 50000
TARGET_INTERACTIONS_ROWS = 25000
RANDOM_STATE = 42

FINAL_COLUMNS = [
    "source_dataset",
    "flow_type",
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
    "friction_level"
]


# ==============================
# HELPER FUNCTIONS
# ==============================

def safe_numeric(series, default=0):
    return pd.to_numeric(series, errors="coerce").fillna(default)


def ensure_file_exists(file_path):
    if not file_path.exists():
        raise FileNotFoundError(f"Dataset file not found: {file_path}")


def clean_final_dataset(df):
    """
    Makes sure every transformed dataset follows the same final GAgent structure.
    """

    df = df.copy()

    for col in FINAL_COLUMNS:
        if col not in df.columns:
            if col == "friction_level":
                df[col] = "Unlabeled"
            elif col in ["source_dataset", "flow_type"]:
                df[col] = "unknown"
            else:
                df[col] = -1

    numeric_cols = [
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
        "error_message_clarity"
    ]

    for col in numeric_cols:
        df[col] = pd.to_numeric(df[col], errors="coerce").fillna(-1)

    count_cols = [
        "click_count",
        "scroll_count",
        "keyboard_count",
        "retry_count",
        "error_count",
        "failed_clicks",
        "screenshot_count"
    ]

    for col in count_cols:
        df[col] = df[col].clip(lower=0)

    df["source_dataset"] = df["source_dataset"].fillna("unknown").astype(str)
    df["flow_type"] = df["flow_type"].fillna("unknown").astype(str)

    return df[FINAL_COLUMNS]


def assign_friction_labels(df):
    """
    Creates rule-based UX friction labels.
    These labels are estimated because the online datasets do not contain perfect UX friction labels.
    """

    df = df.copy()

    usable_numeric_cols = [
        "completion_time",
        "click_count",
        "scroll_count",
        "keyboard_count",
        "retry_count",
        "error_count",
        "failed_clicks",
        "feedback_delay"
    ]

    thresholds = {}

    for col in usable_numeric_cols:
        valid_values = df[df[col] >= 0][col]

        if len(valid_values) > 0:
            thresholds[col] = {
                "p75": valid_values.quantile(0.75),
                "p90": valid_values.quantile(0.90)
            }
        else:
            thresholds[col] = {
                "p75": np.inf,
                "p90": np.inf
            }

    friction_scores = []

    for _, row in df.iterrows():
        score = 0

        # Task failure is a strong friction signal.
        if row["task_completed"] == 0:
            score += 3

        # Completion time
        if row["completion_time"] >= 0:
            if row["completion_time"] > thresholds["completion_time"]["p90"]:
                score += 2
            elif row["completion_time"] > thresholds["completion_time"]["p75"]:
                score += 1

        # Click count
        if row["click_count"] > thresholds["click_count"]["p90"]:
            score += 2
        elif row["click_count"] > thresholds["click_count"]["p75"]:
            score += 1

        # Scroll count
        if row["scroll_count"] > thresholds["scroll_count"]["p90"]:
            score += 2
        elif row["scroll_count"] > thresholds["scroll_count"]["p75"]:
            score += 1

        # Keyboard count
        if row["keyboard_count"] > thresholds["keyboard_count"]["p90"]:
            score += 2
        elif row["keyboard_count"] > thresholds["keyboard_count"]["p75"]:
            score += 1

        # Retry count
        if row["retry_count"] >= 3:
            score += 2
        elif row["retry_count"] >= 1:
            score += 1

        # Error count
        if row["error_count"] >= 3:
            score += 3
        elif row["error_count"] >= 1:
            score += 2

        # Failed clicks
        if row["failed_clicks"] >= 3:
            score += 2
        elif row["failed_clicks"] >= 1:
            score += 1

        # Feedback delay
        if row["feedback_delay"] >= 6:
            score += 2
        elif row["feedback_delay"] >= 3:
            score += 1

        friction_scores.append(score)

    df["friction_score"] = friction_scores

    df["friction_level"] = pd.cut(
        df["friction_score"],
        bins=[-1, 2, 5, np.inf],
        labels=["Low", "Medium", "High"]
    )

    df["friction_level"] = df["friction_level"].astype(str)

    return df.drop(columns=["friction_score"])


# ==============================
# DATASET 1: interaction_telemetry_large.csv
# ==============================

def process_interaction_telemetry():
    file_path = BEHAVIOURAL_ROOT / "interaction_telemetry_large.csv"
    ensure_file_exists(file_path)

    print(f"Processing: {file_path.name}")

    df = pd.read_csv(file_path, low_memory=False)
    df.columns = df.columns.str.strip()

    required_cols = [
        "Session_ID",
        "Event_Type",
        "Event_Target",
        "Error_Code",
        "Session_Length_s",
        "Dwell_Time_ms",
        "Conversion"
    ]

    for col in required_cols:
        if col not in df.columns:
            df[col] = np.nan

    df["Session_ID"] = df["Session_ID"].fillna("unknown_session").astype(str)
    df["Event_Type"] = df["Event_Type"].fillna("").astype(str).str.lower()
    df["Event_Target"] = df["Event_Target"].fillna("unknown").astype(str)
    df["Error_Code"] = df["Error_Code"].fillna("").astype(str)

    df["Session_Length_s"] = safe_numeric(df["Session_Length_s"], default=0)
    df["Dwell_Time_ms"] = safe_numeric(df["Dwell_Time_ms"], default=0)
    df["Conversion"] = safe_numeric(df["Conversion"], default=0)

    df["has_error"] = df["Error_Code"].apply(
        lambda x: 0 if str(x).strip().lower() in ["", "nan", "none", "0"] else 1
    )

    df["is_click"] = df["Event_Type"].str.contains("click", case=False, na=False).astype(int)
    df["is_scroll"] = df["Event_Type"].str.contains("scroll", case=False, na=False).astype(int)
    df["is_keyboard"] = df["Event_Type"].str.contains("input|key|type|keyboard", case=False, na=False).astype(int)

    grouped = df.groupby("Session_ID").agg(
        source_dataset=("Session_ID", lambda x: "interaction_telemetry_large.csv"),
        flow_type=("Event_Target", lambda x: x.mode().iloc[0] if not x.mode().empty else "general_interaction"),
        completion_time=("Session_Length_s", "max"),
        click_count=("is_click", "sum"),
        scroll_count=("is_scroll", "sum"),
        keyboard_count=("is_keyboard", "sum"),
        error_count=("has_error", "sum"),
        feedback_delay=("Dwell_Time_ms", lambda x: x.mean() / 1000),
        task_completed=("Conversion", "max")
    ).reset_index()

    retry_data = (
        df.groupby(["Session_ID", "Event_Target"])
        .size()
        .reset_index(name="target_count")
    )

    retry_data["retry_extra"] = retry_data["target_count"].apply(lambda x: max(x - 1, 0))

    retry_summary = (
        retry_data.groupby("Session_ID")["retry_extra"]
        .sum()
        .reset_index(name="retry_count")
    )

    grouped = grouped.merge(retry_summary, on="Session_ID", how="left")
    grouped["retry_count"] = grouped["retry_count"].fillna(0)

    grouped["failed_clicks"] = np.where(
        (grouped["click_count"] > 0) & (grouped["error_count"] > 0),
        grouped["error_count"],
        0
    )

    grouped["screenshot_count"] = 0
    grouped["error_message_clarity"] = -1

    grouped = grouped.drop(columns=["Session_ID"], errors="ignore")

    return clean_final_dataset(grouped)


# ==============================
# DATASET 2: interactions.csv
# ==============================

def process_interactions_large():
    file_path = BEHAVIOURAL_ROOT / "interactions.csv"
    ensure_file_exists(file_path)

    print(f"Processing large file in chunks: {file_path.name}")

    chunk_results = []
    chunk_size = 200_000

    for chunk_number, chunk in enumerate(pd.read_csv(file_path, chunksize=chunk_size, low_memory=False), start=1):
        print(f"Processing chunk {chunk_number}")

        chunk.columns = chunk.columns.str.strip()

        required_cols = [
            "user_id",
            "timestamp",
            "url",
            "mouse.clicks",
            "scroll.absolute.x",
            "scroll.absolute.y",
            "scroll.relative.x",
            "scroll.relative.y",
            "keyboard"
        ]

        for col in required_cols:
            if col not in chunk.columns:
                chunk[col] = np.nan

        chunk["user_id"] = chunk["user_id"].fillna("unknown_user").astype(str)
        chunk["url"] = chunk["url"].fillna("unknown_flow").astype(str)

        # Create smaller session windows inside the large file.
        # This prevents the dataset from collapsing into too few rows.
        chunk = chunk.reset_index(drop=True)
        chunk["session_window"] = chunk.index // 50
        chunk["synthetic_session_id"] = (
            chunk["user_id"] + "_" +
            chunk["url"].astype(str).str.slice(0, 40) + "_" +
            chunk_number.astype(str) if False else ""
        )

        chunk["synthetic_session_id"] = (
            chunk["user_id"].astype(str)
            + "_chunk" + str(chunk_number)
            + "_window" + chunk["session_window"].astype(str)
        )

        chunk["timestamp_parsed"] = pd.to_datetime(chunk["timestamp"], errors="coerce")

        if chunk["timestamp_parsed"].isna().all():
            chunk["timestamp_numeric"] = chunk.index.astype(float)
        else:
            chunk["timestamp_numeric"] = chunk["timestamp_parsed"].astype("int64") / 1_000_000_000
            chunk["timestamp_numeric"] = chunk["timestamp_numeric"].replace([np.inf, -np.inf], np.nan)

        chunk["is_click"] = chunk["mouse.clicks"].astype(str).str.lower().isin(["true", "1", "yes"]).astype(int)
        chunk["is_keyboard"] = chunk["keyboard"].astype(str).str.lower().isin(["true", "1", "yes"]).astype(int)

        scroll_cols = [
            "scroll.absolute.x",
            "scroll.absolute.y",
            "scroll.relative.x",
            "scroll.relative.y"
        ]

        for col in scroll_cols:
            chunk[col] = pd.to_numeric(chunk[col], errors="coerce").fillna(0)

        chunk["is_scroll"] = (
            (chunk["scroll.absolute.x"] != 0) |
            (chunk["scroll.absolute.y"] != 0) |
            (chunk["scroll.relative.x"] != 0) |
            (chunk["scroll.relative.y"] != 0)
        ).astype(int)

        grouped = chunk.groupby("synthetic_session_id").agg(
            source_dataset=("synthetic_session_id", lambda x: "interactions.csv"),
            flow_type=("url", lambda x: x.mode().iloc[0] if not x.mode().empty else "unknown_flow"),
            min_time=("timestamp_numeric", "min"),
            max_time=("timestamp_numeric", "max"),
            event_count=("synthetic_session_id", "count"),
            click_count=("is_click", "sum"),
            scroll_count=("is_scroll", "sum"),
            keyboard_count=("is_keyboard", "sum")
        ).reset_index(drop=True)

        grouped["completion_time"] = grouped["max_time"] - grouped["min_time"]
        grouped["completion_time"] = grouped["completion_time"].replace([np.inf, -np.inf], np.nan).fillna(0)

        grouped["feedback_delay"] = grouped["completion_time"] / grouped["event_count"].replace(0, 1)

        grouped["error_count"] = 0
        grouped["task_completed"] = -1

        grouped["retry_count"] = np.where(
            grouped["click_count"] > 5,
            ((grouped["click_count"] - 5) / 5).astype(int),
            0
        )

        grouped["failed_clicks"] = np.where(
            (grouped["click_count"] > 10) & (grouped["scroll_count"] == 0),
            grouped["click_count"] - 10,
            0
        )

        grouped["screenshot_count"] = 0
        grouped["error_message_clarity"] = -1

        grouped = grouped.drop(columns=["min_time", "max_time", "event_count"], errors="ignore")
        grouped = clean_final_dataset(grouped)

        # Keep each chunk controlled so memory does not explode.
        if len(grouped) > 1000:
            grouped = grouped.sample(n=1000, random_state=RANDOM_STATE + chunk_number).reset_index(drop=True)

        chunk_results.append(grouped)

    if len(chunk_results) == 0:
        return pd.DataFrame(columns=FINAL_COLUMNS)

    final_df = pd.concat(chunk_results, ignore_index=True)

    # Important:
    # Do NOT group only by source_dataset and flow_type here.
    # That was the reason the old output became too small.
    if len(final_df) > TARGET_INTERACTIONS_ROWS:
        final_df = final_df.sample(n=TARGET_INTERACTIONS_ROWS, random_state=RANDOM_STATE).reset_index(drop=True)

    return clean_final_dataset(final_df)


# ==============================
# DATASET 3: e-shop clothing 2008.csv
# ==============================

def process_eshop():
    file_path = BEHAVIOURAL_ROOT / "e-shop clothing 2008.csv"
    ensure_file_exists(file_path)

    print(f"Processing: {file_path.name}")

    df = pd.read_csv(file_path, sep=";", low_memory=False)
    df.columns = df.columns.str.strip()

    session_col = "session ID"

    if session_col not in df.columns:
        print("e-shop dataset skipped because session ID column is missing.")
        return pd.DataFrame(columns=FINAL_COLUMNS)

    df[session_col] = df[session_col].fillna("unknown_session").astype(str)

    if "page 1 (main category)" in df.columns:
        flow_col = "page 1 (main category)"
    elif "page" in df.columns:
        flow_col = "page"
    else:
        df["flow_type_fallback"] = "ecommerce_browsing"
        flow_col = "flow_type_fallback"

    grouped = df.groupby(session_col).agg(
        source_dataset=(session_col, lambda x: "e-shop clothing 2008.csv"),
        flow_type=(flow_col, lambda x: x.mode().iloc[0] if not x.mode().empty else "ecommerce_browsing"),
        click_count=(session_col, "count")
    ).reset_index()

    grouped["completion_time"] = grouped["click_count"]

    grouped["scroll_count"] = -1
    grouped["keyboard_count"] = -1

    if "page 2 (clothing model)" in df.columns:
        retry_data = (
            df.groupby([session_col, "page 2 (clothing model)"])
            .size()
            .reset_index(name="count")
        )

        retry_data["retry_extra"] = retry_data["count"].apply(lambda x: max(x - 1, 0))

        retry_summary = (
            retry_data.groupby(session_col)["retry_extra"]
            .sum()
            .reset_index(name="retry_count")
        )

        grouped = grouped.merge(retry_summary, on=session_col, how="left")
        grouped["retry_count"] = grouped["retry_count"].fillna(0)
    else:
        grouped["retry_count"] = 0

    grouped["error_count"] = 0
    grouped["failed_clicks"] = 0
    grouped["feedback_delay"] = -1
    grouped["task_completed"] = -1
    grouped["screenshot_count"] = 0
    grouped["error_message_clarity"] = -1

    grouped = grouped.drop(columns=[session_col], errors="ignore")

    return clean_final_dataset(grouped)


# ==============================
# FINAL BALANCING
# ==============================

def create_final_50k_dataset(processed_datasets):
    combined_df = pd.concat(processed_datasets, ignore_index=True)
    combined_df = clean_final_dataset(combined_df)

    if len(combined_df) > TARGET_FINAL_ROWS:
        combined_df = combined_df.sample(n=TARGET_FINAL_ROWS, random_state=RANDOM_STATE).reset_index(drop=True)

    elif len(combined_df) < TARGET_FINAL_ROWS:
        print("\nWarning:")
        print(f"Only {len(combined_df)} real processed rows were created.")
        print("The script will NOT duplicate rows just to fake 50K.")
        print("Final dataset will use all available real processed rows.")

    combined_df = assign_friction_labels(combined_df)
    combined_df = clean_final_dataset(combined_df)

    return combined_df


# ==============================
# MAIN SCRIPT
# ==============================

def main():
    print("Starting Phase 2 feature engineering...")
    print(f"Target final rows: {TARGET_FINAL_ROWS}")

    processed_datasets = []

    telemetry_df = process_interaction_telemetry()
    telemetry_df.to_csv(OUTPUT_DIR / "processed_interaction_telemetry_large.csv", index=False)
    processed_datasets.append(telemetry_df)

    interactions_df = process_interactions_large()
    interactions_df.to_csv(OUTPUT_DIR / "processed_interactions.csv", index=False)
    processed_datasets.append(interactions_df)

    eshop_df = process_eshop()
    eshop_df.to_csv(OUTPUT_DIR / "processed_eshop_clothing_2008.csv", index=False)
    processed_datasets.append(eshop_df)

    combined_df = create_final_50k_dataset(processed_datasets)

    combined_df.to_csv(OUTPUT_DIR / "feature_engineered_interaction_dataset.csv", index=False)
    combined_df.to_csv(OUTPUT_DIR / "ux_friction_dataset.csv", index=False)

    print("\nFeature engineering completed.")
    print(f"Saved: {OUTPUT_DIR / 'feature_engineered_interaction_dataset.csv'}")
    print(f"Saved: {OUTPUT_DIR / 'ux_friction_dataset.csv'}")

    print("\nFinal dataset shape:")
    print(combined_df.shape)

    print("\nSource dataset distribution:")
    print(combined_df["source_dataset"].value_counts())

    print("\nFriction label distribution:")
    print(combined_df["friction_level"].value_counts())


if __name__ == "__main__":
    main()
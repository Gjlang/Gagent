import os
import pandas as pd
from pathlib import Path

DATASET_ROOT = r"D:\FYP\Dataset"

OUTPUT_FILE = r"D:\FYP\GAgent\GAgent\phase2_dataset_preparation\outputs\inventory\dataset_inventory.xlsx"

GROUP_RULES = {
    "Behavioral interaction datasets": "Behavioural Interaction Dataset",
    "Text review datasets": "Text Review Dataset",
    "UX survey  usability datasets": "UX Survey / Usability Dataset"
}

PRIORITY_RULES = {
    "interaction_telemetry_large.csv": "Highest",
    "interactions.csv": "High",
    "e-shop clothing 2008.csv": "Medium",
    "dataset.csv": "Optional",
    "google_play_store_reviews.csv": "Optional",
    "GooglePlay_App_Data.csv": "Optional",
    "reviews.csv": "Optional",
    "UI UX Dataset.csv": "Research validation",
    "usability_testing_large.csv": "Research validation",
    "survey_questionnaire_large.csv": "Research validation"
}

def detect_group(file_path):
    for key, value in GROUP_RULES.items():
        if key in file_path:
            return value
    return "Unknown"

def detect_separator(file_name):
    if file_name == "e-shop clothing 2008.csv":
        return ";"
    return ","

def recommended_use(file_name):
    if file_name in ["interaction_telemetry_large.csv", "interactions.csv"]:
        return "Use for main UX friction ML dataset"
    if file_name == "e-shop clothing 2008.csv":
        return "Use carefully as medium-priority behavioural proxy dataset"
    if file_name in ["dataset.csv", "google_play_store_reviews.csv", "GooglePlay_App_Data.csv", "reviews.csv"]:
        return "Optional NLP support only"
    return "Research validation and documentation support only"

def label_availability(columns):
    possible_labels = ["label", "friction", "sentiment", "score", "rating", "Task_Success", "Conversion", "Frustration_Level"]
    found = [col for col in columns if any(keyword.lower() in col.lower() for keyword in possible_labels)]
    return ", ".join(found) if found else "No direct label"

inventory = []

for root, dirs, files in os.walk(DATASET_ROOT):
    for file in files:
        if file.lower().endswith(".csv"):
            file_path = os.path.join(root, file)
            sep = detect_separator(file)

            try:
                df = pd.read_csv(file_path, sep=sep, encoding="utf-8-sig", low_memory=False)

                inventory.append({
                    "file_name": file,
                    "file_path": file_path,
                    "dataset_group": detect_group(file_path),
                    "number_of_rows": len(df),
                    "number_of_columns": len(df.columns),
                    "column_names": ", ".join(df.columns),
                    "useful_columns": "To be mapped in Sub Phase 2.5",
                    "missing_values": int(df.isna().sum().sum()),
                    "duplicate_rows": int(df.duplicated().sum()),
                    "label_availability": label_availability(df.columns),
                    "recommended_use": recommended_use(file),
                    "priority": PRIORITY_RULES.get(file, "Unknown")
                })

            except MemoryError:
                inventory.append({
                    "file_name": file,
                    "file_path": file_path,
                    "dataset_group": detect_group(file_path),
                    "number_of_rows": "Large file - process with chunks",
                    "number_of_columns": "Check with chunk reading",
                    "column_names": "Large file - check separately",
                    "useful_columns": "To be mapped in Sub Phase 2.5",
                    "missing_values": "Check with chunk reading",
                    "duplicate_rows": "Check with chunk reading",
                    "label_availability": "Unknown",
                    "recommended_use": recommended_use(file),
                    "priority": PRIORITY_RULES.get(file, "Unknown")
                })

inventory_df = pd.DataFrame(inventory)
inventory_df.to_excel(str(OUTPUT_FILE), index=False)
print("Dataset inventory created:")
print(OUTPUT_FILE)
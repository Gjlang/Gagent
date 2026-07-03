from pathlib import Path
import json
import joblib


BASE_DIR = Path(__file__).resolve().parents[1]

MODELS_DIR = BASE_DIR / "models"

MODEL_PATH = MODELS_DIR / "ux_friction_model.pkl"
RANDOM_FOREST_PATH = MODELS_DIR / "random_forest_model.pkl"
LOGISTIC_REGRESSION_PATH = MODELS_DIR / "logistic_regression_model.pkl"
DECISION_TREE_PATH = MODELS_DIR / "decision_tree_model.pkl"
LABEL_ENCODER_PATH = MODELS_DIR / "label_encoder.pkl"
SCALER_PATH = MODELS_DIR / "scaler.pkl"
FEATURE_COLUMNS_PATH = MODELS_DIR / "feature_columns.json"


def check_file_exists(file_path):
    print("-" * 80)
    print(f"Checking file: {file_path}")

    if file_path.exists():
        print("Status: FOUND")
        print(f"File size: {file_path.stat().st_size} bytes")
        return True
    else:
        print("Status: NOT FOUND")
        return False


def inspect_model(file_path, model_name):
    if not check_file_exists(file_path):
        return

    try:
        model = joblib.load(file_path)

        print(f"\n{model_name} loaded successfully.")
        print(f"Object type: {type(model)}")
        print("\nModel summary:")
        print(model)

        if hasattr(model, "get_params"):
            print("\nModel parameters:")
            params = model.get_params()
            for key, value in params.items():
                print(f"{key}: {value}")

        if hasattr(model, "classes_"):
            print("\nModel classes:")
            print(model.classes_)

        if hasattr(model, "feature_importances_"):
            print("\nFeature importances available: YES")
        else:
            print("\nFeature importances available: NO")

    except Exception as error:
        print(f"Error loading {model_name}: {error}")


def inspect_label_encoder():
    if not check_file_exists(LABEL_ENCODER_PATH):
        return

    try:
        label_encoder = joblib.load(LABEL_ENCODER_PATH)

        print("\nLabel Encoder loaded successfully.")
        print(f"Object type: {type(label_encoder)}")
        print("Classes:")
        print(label_encoder.classes_)

    except Exception as error:
        print(f"Error loading label encoder: {error}")


def inspect_scaler():
    if not check_file_exists(SCALER_PATH):
        return

    try:
        scaler = joblib.load(SCALER_PATH)

        print("\nScaler loaded successfully.")
        print(f"Object type: {type(scaler)}")
        print("Scaler summary:")
        print(scaler)

        if hasattr(scaler, "mean_"):
            print("\nScaler mean values:")
            print(scaler.mean_)

        if hasattr(scaler, "scale_"):
            print("\nScaler scale values:")
            print(scaler.scale_)

    except Exception as error:
        print(f"Error loading scaler: {error}")


def inspect_feature_columns():
    if not check_file_exists(FEATURE_COLUMNS_PATH):
        return

    try:
        with open(FEATURE_COLUMNS_PATH, "r", encoding="utf-8") as file:
            feature_columns = json.load(file)

        print("\nFeature columns loaded successfully.")
        print(f"Total features: {len(feature_columns)}")
        print("Features used by the model:")

        for index, feature in enumerate(feature_columns, start=1):
            print(f"{index}. {feature}")

    except Exception as error:
        print(f"Error loading feature columns: {error}")


def test_sample_prediction():
    print("\n" + "=" * 80)
    print("Testing sample prediction using saved PKL files")
    print("=" * 80)

    try:
        model = joblib.load(MODEL_PATH)
        label_encoder = joblib.load(LABEL_ENCODER_PATH)
        scaler = joblib.load(SCALER_PATH)

        with open(FEATURE_COLUMNS_PATH, "r", encoding="utf-8") as file:
            feature_columns = json.load(file)

        sample_input = {
            "completion_time": 18000,
            "click_count": 18,
            "scroll_count": 8,
            "keyboard_count": 10,
            "retry_count": 5,
            "error_count": 4,
            "failed_clicks": 5,
            "feedback_delay": 3500,
            "task_completed": 0,
            "screenshot_count": 5,
            "error_message_clarity": 0,
        }

        sample_data = [[sample_input[feature] for feature in feature_columns]]

        sample_data_scaled = scaler.transform(sample_data)

        prediction = model.predict(sample_data_scaled)
        predicted_label = label_encoder.inverse_transform(prediction)

        print("Sample input:")
        for key, value in sample_input.items():
            print(f"{key}: {value}")

        print("\nPredicted friction level:")
        print(predicted_label[0])

        if hasattr(model, "predict_proba"):
            probabilities = model.predict_proba(sample_data_scaled)

            print("\nPrediction probabilities:")
            for label, probability in zip(label_encoder.classes_, probabilities[0]):
                print(f"{label}: {probability:.4f}")

    except Exception as error:
        print(f"Sample prediction failed: {error}")


def main():
    print("=" * 80)
    print("GAgent Phase 4 PKL File Checker")
    print("=" * 80)
    print(f"Models folder: {MODELS_DIR}")

    inspect_model(MODEL_PATH, "Final Best Model")
    inspect_model(RANDOM_FOREST_PATH, "Random Forest Model")
    inspect_model(LOGISTIC_REGRESSION_PATH, "Logistic Regression Model")
    inspect_model(DECISION_TREE_PATH, "Decision Tree Model")

    inspect_label_encoder()
    inspect_scaler()
    inspect_feature_columns()

    test_sample_prediction()

    print("\n" + "=" * 80)
    print("PKL file checking completed.")
    print("=" * 80)


if __name__ == "__main__":
    main()
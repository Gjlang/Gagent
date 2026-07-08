package com.gagent.dummyandroid;

import android.app.Activity;
import android.app.AlertDialog;
import android.os.Bundle;
import android.os.Handler;
import android.view.Gravity;
import android.view.View;
import android.widget.*;
import android.graphics.Color;
import android.text.InputType;

public class MainActivity extends Activity {

    private LinearLayout root;
    private ScrollView scrollView;
    private String scenario = "good";
    private Handler handler = new Handler();

    private TextView statusMessage;
    private TextView errorMessage;
    private TextView successMessage;

    private long screenStartTime;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        // Controlled slow app launch simulation.
        // This is short enough to avoid real ANR but measurable by Appium.
        try {
            Thread.sleep(800);
        } catch (InterruptedException ignored) {}

        showHome();
    }

    private void baseScreen(String title) {
        screenStartTime = System.currentTimeMillis();

        scrollView = new ScrollView(this);
        root = new LinearLayout(this);
        root.setOrientation(LinearLayout.VERTICAL);
        root.setPadding(32, 32, 32, 32);
        scrollView.addView(root);

        TextView titleView = new TextView(this);
        titleView.setText(title);
        titleView.setTextSize(24);
        titleView.setTextColor(Color.BLACK);
        titleView.setPadding(0, 0, 0, 24);
        root.addView(titleView);

        statusMessage = makeMessageView("status_message", Color.DKGRAY);
        errorMessage = makeMessageView("error_message", Color.RED);
        successMessage = makeMessageView("success_message", Color.rgb(0, 120, 0));

        setContentView(scrollView);
    }

    private TextView makeMessageView(String idName, int color) {
        TextView view = new TextView(this);
        view.setText("");
        view.setTextSize(16);
        view.setTextColor(color);
        view.setPadding(0, 16, 0, 16);
        view.setId(getResources().getIdentifier(idName, "id", getPackageName()));
        root.addView(view);
        return view;
    }

    private Button makeButton(String label, String idName) {
        Button button = new Button(this);
        button.setText(label);
        button.setAllCaps(false);
        button.setId(getResources().getIdentifier(idName, "id", getPackageName()));
        root.addView(button);
        return button;
    }

    private EditText makeInput(String hint, String idName) {
        EditText input = new EditText(this);
        input.setHint(hint);
        input.setSingleLine(true);
        input.setId(getResources().getIdentifier(idName, "id", getPackageName()));
        root.addView(input);
        return input;
    }

    private void showHome() {
        baseScreen("GAgent Controlled Android UX Friction App");

        TextView info = new TextView(this);
        info.setText("Select scenario type, then choose a flow. This app is only for controlled Appium UX-friction testing.");
        info.setTextSize(16);
        root.addView(info);

        Spinner spinner = new Spinner(this);
        spinner.setId(getResources().getIdentifier("scenario_spinner", "id", getPackageName()));
        String[] scenarios = {"good", "medium", "bad"};
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, scenarios);
        spinner.setAdapter(adapter);
        root.addView(spinner);

        spinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                scenario = scenarios[position];
            }

            public void onNothingSelected(AdapterView<?> parent) {}
        });

        Button login = makeButton("Login Flow", "flow_login_button");
        Button signup = makeButton("Signup Flow", "flow_signup_button");
        Button search = makeButton("Search Flow", "flow_search_button");
        Button buttonClick = makeButton("Button Click Flow", "flow_button_click_button");
        Button formSubmit = makeButton("Form Submit Flow", "flow_form_submit_button");

        login.setOnClickListener(v -> loadScreenWithScenario("login"));
        signup.setOnClickListener(v -> loadScreenWithScenario("signup"));
        search.setOnClickListener(v -> loadScreenWithScenario("search"));
        buttonClick.setOnClickListener(v -> loadScreenWithScenario("button_click"));
        formSubmit.setOnClickListener(v -> loadScreenWithScenario("form_submit"));
    }

    private void loadScreenWithScenario(String flow) {
        statusMessage.setText("Loading " + flow + " screen...");

        int delay = 0;

        if (scenario.equals("medium")) {
            delay = 900;
        } else if (scenario.equals("bad")) {
            delay = 1800;
        }

        int finalDelay = delay;

        handler.postDelayed(() -> {
            if (flow.equals("login")) showLogin();
            if (flow.equals("signup")) showSignup();
            if (flow.equals("search")) showSearch();
            if (flow.equals("button_click")) showButtonClick();
            if (flow.equals("form_submit")) showFormSubmit();

            if (finalDelay > 0 && statusMessage != null) {
                statusMessage.setText("Screen loaded after " + finalDelay + " ms");
            }
        }, delay);
    }

    private void maybeShowPopup() {
        if (scenario.equals("medium") || scenario.equals("bad")) {
            TextView modalContent = new TextView(this);
            modalContent.setText("Popup blocking action. Close this popup to continue.");
            modalContent.setPadding(32, 32, 32, 32);
            modalContent.setId(getResources().getIdentifier("popup_modal", "id", getPackageName()));

            AlertDialog dialog = new AlertDialog.Builder(this)
                    .setView(modalContent)
                    .setPositiveButton("Close", null)
                    .create();

            dialog.setOnShowListener(d -> {
                Button close = dialog.getButton(AlertDialog.BUTTON_POSITIVE);
                close.setId(getResources().getIdentifier("popup_close_button", "id", getPackageName()));
            });

            dialog.show();
        }
    }

    private void showLogin() {
        baseScreen("Login Flow - " + scenario);

        EditText email = makeInput("Email", "login_email_input");
        EditText password = makeInput("Password", "login_password_input");
        password.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_PASSWORD);

        Button submit = makeButton("Login", "login_submit_button");

        if (scenario.equals("medium")) {
            maybeShowPopup();
        }

        if (scenario.equals("bad")) {
            submit.setText("Continue");
        }

        submit.setOnClickListener(v -> {
            if (scenario.equals("good")) {
                successMessage.setText("Login successful");
                errorMessage.setText("");
            } else if (scenario.equals("medium")) {
                delayFeedback(900, "Login completed after slow response", false);
            } else {
                if (email.getText().toString().trim().isEmpty()) {
                    errorMessage.setText("Error");
                } else {
                    timeoutSimulation();
                }
            }
        });
    }

    private void showSignup() {
        baseScreen("Signup Flow - " + scenario);

        EditText name = makeInput("Name", "signup_name_input");
        EditText email = makeInput("Email", "signup_email_input");

        Button submit = makeButton("Sign Up", "signup_submit_button");

        if (scenario.equals("bad")) {
            submit.setEnabled(false);
            errorMessage.setText("Submit button disabled until all fields are valid.");
        }

        submit.setOnClickListener(v -> {
            if (scenario.equals("good")) {
                successMessage.setText("Signup successful");
                errorMessage.setText("");
            } else if (scenario.equals("medium")) {
                if (!email.getText().toString().contains("@")) {
                    errorMessage.setText("Please enter a valid email address.");
                } else {
                    delayFeedback(900, "Signup completed after validation delay", false);
                }
            } else {
                errorMessage.setText("Something went wrong");
            }
        });
    }

    private void showSearch() {
        baseScreen("Search Flow - " + scenario);

        EditText input = makeInput("Search keyword", "search_input");
        Button search = makeButton("Search", "search_submit_button");

        if (scenario.equals("medium")) {
            maybeShowPopup();
        }

        if (scenario.equals("bad")) {
            TextView wrongPath = new TextView(this);
            wrongPath.setText("Search button below may navigate to the wrong result page.");
            root.addView(wrongPath);
        }

        search.setOnClickListener(v -> {
            if (scenario.equals("good")) {
                successMessage.setText("Search results loaded");
                errorMessage.setText("");
            } else if (scenario.equals("medium")) {
                delayFeedback(1000, "Search results loaded slowly", false);
            } else {
                errorMessage.setText("No result");
                statusMessage.setText("Wrong navigation path detected: opened unrelated result state.");
            }
        });
    }

    private void showButtonClick() {
        baseScreen("Button Click Flow - " + scenario);

        Button mainCta = makeButton("Main CTA", "main_cta_button");

        if (scenario.equals("bad")) {
            Space space = new Space(this);
            space.setMinimumHeight(1200);
            root.addView(space);

            Button hiddenButton = makeButton("Hidden Button Requiring Scroll", "hidden_scroll_button");

            Button smallButton = new Button(this);
            smallButton.setText("tiny");
            smallButton.setTextSize(9);
            smallButton.setWidth(70);
            smallButton.setHeight(50);
            smallButton.setId(getResources().getIdentifier("small_tap_button", "id", getPackageName()));
            root.addView(smallButton);

            smallButton.setOnClickListener(v -> {
                errorMessage.setText("Small button clicked. Difficult-to-tap target recorded.");
            });

            hiddenButton.setOnClickListener(v -> {
                successMessage.setText("Hidden button found after scroll");
            });
        }

        mainCta.setOnClickListener(v -> {
            if (scenario.equals("good")) {
                successMessage.setText("Main CTA completed");
                errorMessage.setText("");
            } else if (scenario.equals("medium")) {
                delayFeedback(1000, "CTA completed after slow response", false);
            } else {
                controlledFrozenScreen();
            }
        });
    }

    private void showFormSubmit() {
        baseScreen("Form Submit Flow - " + scenario);

        EditText name = makeInput("Full name", "form_name_input");
        EditText email = makeInput("Email", "form_email_input");
        Button submit = makeButton("Submit Form", "form_submit_button");

        submit.setOnClickListener(v -> {
            String nameValue = name.getText().toString().trim();
            String emailValue = email.getText().toString().trim();

            if (scenario.equals("good")) {
                successMessage.setText("Form submitted successfully");
                errorMessage.setText("");
            } else if (scenario.equals("medium")) {
                if (nameValue.isEmpty()) {
                    errorMessage.setText("Full name is required.");
                } else if (!emailValue.contains("@")) {
                    errorMessage.setText("Email must contain @ symbol.");
                } else {
                    delayFeedback(900, "Form submitted after validation delay", false);
                }
            } else {
                if (nameValue.isEmpty() || emailValue.isEmpty()) {
                    errorMessage.setText("Invalid form");
                } else {
                    timeoutSimulation();
                }
            }
        });
    }

    private void delayFeedback(int delayMs, String message, boolean isError) {
        statusMessage.setText("Processing...");
        handler.postDelayed(() -> {
            if (isError) {
                errorMessage.setText(message);
            } else {
                successMessage.setText(message);
            }
            statusMessage.setText("");
        }, delayMs);
    }

    private void timeoutSimulation() {
        statusMessage.setText("Request timeout simulation started...");
        handler.postDelayed(() -> {
            errorMessage.setText("Request timed out. Please try again.");
            statusMessage.setText("");
        }, 3000);
    }

    private void controlledFrozenScreen() {
        statusMessage.setText("Controlled frozen-screen simulation. UI response intentionally delayed.");
        handler.postDelayed(() -> {
            errorMessage.setText("ANR-like delay simulated safely without crashing the app.");
            statusMessage.setText("");
        }, 4000);
    }
}
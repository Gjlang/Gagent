const fs = require("fs");
const path = require("path");

const finalExportDir = path.join(__dirname, "../outputs/final_export");
const version = process.argv[2] || "v2";

const filesToCopy = [
  ["ux_friction_dataset.csv", `ux_friction_dataset_${version}.csv`],
  [
    "combined_ux_friction_dataset.csv",
    `combined_ux_friction_dataset_${version}.csv`,
  ],
];

fs.mkdirSync(finalExportDir, { recursive: true });

filesToCopy.forEach(([sourceName, targetName]) => {
  const sourcePath = path.join(finalExportDir, sourceName);
  const targetPath = path.join(finalExportDir, targetName);

  if (!fs.existsSync(sourcePath)) {
    console.warn(`Skipped missing file: ${sourcePath}`);
    return;
  }

  fs.copyFileSync(sourcePath, targetPath);
  console.log(`Created versioned copy: ${targetPath}`);
});

function delayScore(value, medium, high, mediumPoints, highPoints) {
  if (value >= high) return highPoints;
  if (value >= medium) return mediumPoints;
  return 0;
}

function calculateFrictionScore(row) {
  let score = 0;

  score += Number(row.task_failed) * 30;
  score += Math.min(Number(row.error_count) || 0, 5) * 6;
  score += Math.min(Number(row.failed_clicks) || 0, 5) * 7;
  score += Math.min(Number(row.retry_count) || 0, 5) * 5;
  score += Math.min(Number(row.unnecessary_clicks) || 0, 5) * 3;
  score += Math.min(Number(row.path_deviation_score) || 0, 10) * 2;

  score += delayScore(Number(row.page_load_time_ms) || 0, 1800, 4500, 7, 15);
  score += delayScore(Number(row.feedback_delay_ms) || 0, 1200, 5000, 8, 18);
  score += delayScore(Number(row.interaction_to_next_paint_ms) || 0, 200, 700, 3, 8);

  const cls = Number(row.cumulative_layout_shift) || 0;
  if (cls >= 0.25) score += 14;
  else if (cls >= 0.1) score += 7;

  score += Number(row.popup_detected) ? 8 : 0;
  score += Number(row.cookie_banner_detected) ? 6 : 0;
  score += Number(row.overlay_blocks_cta) ? 14 : 0;

  const clarity = Number(row.error_message_clarity);
  if (Number(row.error_message_present) && clarity === 0) score += 8;
  else if (Number(row.error_message_present) && clarity === 1) score += 4;

  if (Number(row.scroll_count) > 1) score += 3;
  if (Number(row.click_count) > 3) score += 3;
  if (Number(row.completion_time) > 8) score += 7;
  else if (Number(row.completion_time) > 4) score += 3;

  return Math.round(score);
}

function labelFromScore(score) {
  if (score < 20) return "Low";
  if (score < 50) return "Medium";
  return "High";
}

function labelRow(row, expectedLabel) {
  const friction_score = calculateFrictionScore(row);
  const friction_level = labelFromScore(friction_score);
  return {
    ...row,
    friction_score,
    friction_level,
    expected_friction_level: expectedLabel,
    label_mismatch: expectedLabel && expectedLabel !== friction_level ? 1 : 0
  };
}

module.exports = {
  calculateFrictionScore,
  labelFromScore,
  labelRow
};

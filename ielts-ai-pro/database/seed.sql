-- IELTS AI Pro - Seed Data (Demo Users, Official Practice Tests & Questions)
-- Demo Passwords:
--   admin@ieltspro.local   -> Admin@12345
--   student@ieltspro.local -> Student@12345
--   sara@ieltspro.local    -> Student@12345

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('site_title', 'IELTS AI Pro'),
('ai_provider', 'offline'),
('gemini_api_key', ''),
('openai_api_key', ''),
('allow_registration', '1');

INSERT INTO `tests` (`id`, `title`, `module`, `exam_type`, `duration_minutes`, `description`, `passage_or_script`, `audio_url`, `is_active`) VALUES
(1, 'Academic Reading Practice Test 1: The Rise of Urban Vertical Farming', 'reading', 'academic', 60,
 'Read the passage on sustainable vertical agriculture and answer Questions 1-8 (True/False/Not Given, Multiple Choice, and Sentence Completion).',
 '<h3>The Rise of Urban Vertical Farming</h3>
<p><strong>Paragraph A:</strong> By the year 2050, nearly 68% of the global population is projected to live in urban areas. Traditional horizontal agriculture faces mounting pressures from soil degradation, freshwater scarcity, and volatile climate patterns. Vertical farming—the practice of cultivating crops in vertically stacked layers inside controlled-environment buildings—has emerged as a promising supplement to conventional farming.</p>
<p><strong>Paragraph B:</strong> First conceptualized in modern academic form by Columbia University professor Dickson Despommier in 1999, vertical farms utilize three primary soil-free systems: hydroponics (roots submerged in nutrient-rich water), aeroponics (roots misted with nutrient vapor), and aquaponics (combining fish farming with hydroponic plant beds). Studies indicate that closed-loop aeroponic systems can reduce freshwater usage by up to 95% compared with open-field agriculture while completely eliminating agricultural runoff.</p>
<p><strong>Paragraph C:</strong> Despite these ecological advantages, vertical farming faces substantial economic hurdles. High startup capital for automated LED lighting arrays and HVAC climate control makes electricity the single largest operational expense after labor. Consequently, most commercial vertical farms focus on fast-growing, high-margin leafy greens, microgreens, and herbs rather than staple calorie crops like wheat, rice, or corn, which require high light intensity and long growing cycles.</p>
<p><strong>Paragraph D:</strong> Proponents argue that locating vertical farms inside or adjacent to metropolitan centers drastically cuts food-miles and spoilage during cold-chain transport. Recent pilot facilities in Singapore, Tokyo, and Dubai have demonstrated that integrating rooftop solar arrays with waste-heat recovery from adjacent data centers can reduce net energy costs by 28% to 35%.</p>',
 NULL, 1),

(2, 'IELTS Listening Practice Test 1: University Library Orientation & Research Workshop', 'listening', 'both', 30,
 'Listen to the conversation between a university librarian and an incoming postgraduate student, then answer Questions 1-6.',
 'LIBRARIAN: Good morning, welcome to the Central Research Library. How can I help you today?
STUDENT: Hi! I just started my Master of Environmental Science program, and I would like to register for extended borrowing privileges and book a group study pod.
LIBRARIAN: Congratulations on starting your program. Postgraduate students can borrow up to 25 print volumes at a time for a period of 42 days, whereas undergraduates are limited to 10 books.
STUDENT: That is generous. What about rare archival journals?
LIBRARIAN: Archival journals are kept on Floor 4 in the Climate-Controlled Reading Room. They cannot leave the building, and you need to submit a digital request form at least 24 hours in advance.
STUDENT: Understood. And how do I reserve the multimedia study pods?
LIBRARIAN: Those are located on Floor 2 in the East Wing. You can book them via the student portal for up to 3 hours per day, provided your group has at least 3 registered students.
STUDENT: Is there a late return penalty for standard books?
LIBRARIAN: Yes, overdue standard books incur a fine of 50 pence per day per item, capped at 15 pounds. Also, every Wednesday at 2:00 PM we run a free citation workshop covering EndNote and Zotero in Room 108.',
 'builtin_tts', 1),

(3, 'Academic Writing Full Test 1: Renewable Energy & Remote Work', 'writing', 'academic', 60,
 'Complete both Writing Task 1 (minimum 150 words, 20 minutes recommended) and Writing Task 2 (minimum 250 words, 40 minutes recommended). Task 2 carries twice the weight of Task 1.',
 '<h4>Writing Task 1 (Academic)</h4><p>The table below shows the percentage of total electricity generated from renewable sources (Solar, Wind, and Hydroelectric) across four countries between 2010 and 2025. Summarize the information by selecting and reporting the main features, and make comparisons where relevant.</p>
<table class="data-table"><thead><tr><th>Country</th><th>2010 (%)</th><th>2015 (%)</th><th>2020 (%)</th><th>2025 (%)</th></tr></thead>
<tbody><tr><td>Germany</td><td>17%</td><td>29%</td><td>44%</td><td>56%</td></tr>
<tr><td>United Kingdom</td><td>7%</td><td>22%</td><td>41%</td><td>51%</td></tr>
<tr><td>Australia</td><td>9%</td><td>14%</td><td>24%</td><td>38%</td></tr>
<tr><td>Japan</td><td>10%</td><td>15%</td><td>20%</td><td>26%</td></tr></tbody></table>
<hr><h4>Writing Task 2 (Essay)</h4><p><em>Many organizations now allow employees to work from home several days a week. Do the advantages of remote working for employees and society outweigh the disadvantages?</em> Give reasons for your answer and include any relevant examples from your own knowledge or experience.</p>',
 NULL, 1),

(4, 'IELTS Speaking Mock Test 1: Technology, Learning & Future Cities', 'speaking', 'both', 15,
 'Complete all three parts of the IELTS Speaking test. You may record your voice using your microphone (with live speech-to-text transcription) or type your spoken response.',
 '<h4>Speaking Assessment Overview</h4><p>Your response is evaluated across the four official IELTS Speaking criteria: <strong>Fluency & Coherence</strong>, <strong>Lexical Resource</strong>, <strong>Grammatical Range & Accuracy</strong>, and <strong>Pronunciation / Delivery</strong>.</p>',
 NULL, 1);

INSERT INTO `questions` (`id`, `test_id`, `question_number`, `question_type`, `question_text`, `options_json`, `correct_answer`, `explanation`, `points`) VALUES
(1, 1, 1, 'tfng', 'By 2050, more than two-thirds of the world population is expected to reside in cities.', '["TRUE", "FALSE", "NOT GIVEN"]', 'TRUE', 'Paragraph A states that nearly 68% of the global population is projected to live in urban areas by 2050 (68% is more than two-thirds, 66.7%).', 1),
(2, 1, 2, 'tfng', 'Dickson Despommier built the world''s first commercial vertical farm in Singapore in 1999.', '["TRUE", "FALSE", "NOT GIVEN"]', 'FALSE', 'Paragraph B notes that Dickson Despommier conceptualized vertical farming in academic form at Columbia University in 1999, not a commercial farm in Singapore.', 1),
(3, 1, 3, 'tfng', 'Aquaponics is more profitable than aeroponics in every European capital.', '["TRUE", "FALSE", "NOT GIVEN"]', 'NOT GIVEN', 'The passage mentions aquaponics and aeroponics in Paragraph B, but makes no comparison of their profitability in European capitals.', 1),
(4, 1, 4, 'mcq', 'According to Paragraph B, how much freshwater can closed-loop aeroponic systems save compared with open-field agriculture?', '["Up to 50%", "Up to 68%", "Up to 95%", "100%"]', 'Up to 95%', 'Paragraph B explicitly states that closed-loop aeroponic systems can reduce freshwater usage by up to 95%.', 1),
(5, 1, 5, 'mcq', 'What is identified in Paragraph C as the largest operational expense after labor for vertical farms?', '["Seed acquisition", "Electricity for LED lighting and HVAC", "Cold-chain transportation", "Freshwater filtration"]', 'Electricity for LED lighting and HVAC', 'Paragraph C states that electricity for automated LED lighting arrays and HVAC climate control is the single largest operational expense after labor.', 1),
(6, 1, 6, 'fill_blank', 'In aeroponic systems, plant roots are misted with nutrient ________ rather than submerged in soil.', NULL, 'vapor|vapour', 'Paragraph B explains that aeroponics involves roots misted with nutrient vapor.', 1),
(7, 1, 7, 'fill_blank', 'Most commercial vertical farms avoid growing staple calorie crops such as wheat, rice, or ________.', NULL, 'corn', 'Paragraph C lists wheat, rice, or corn as staple calorie crops avoided due to high light and cycle requirements.', 1),
(8, 1, 8, 'mcq', 'In Paragraph D, integrating rooftop solar panels with waste-heat recovery from data centers reduced net energy costs by what range?', '["10% to 15%", "18% to 22%", "28% to 35%", "45% to 60%"]', '28% to 35%', 'Paragraph D states that pilot facilities reduced net energy costs by 28% to 35%.', 1),

(9, 2, 1, 'mcq', 'How many print books can a postgraduate student borrow at one time?', '["10 books", "15 books", "25 books", "42 books"]', '25 books', 'The librarian states: "Postgraduate students can borrow up to 25 print volumes at a time for a period of 42 days."', 1),
(10, 2, 2, 'fill_blank', 'Postgraduate students may keep borrowed print volumes for up to ________ days.', NULL, '42', 'The librarian specifies a borrowing period of 42 days.', 1),
(11, 2, 3, 'mcq', 'On which floor are the rare archival journals stored?', '["Floor 1", "Floor 2", "Floor 3", "Floor 4"]', 'Floor 4', 'The librarian states archival journals are kept on Floor 4 in the Climate-Controlled Reading Room.', 1),
(12, 2, 4, 'fill_blank', 'To access archival journals, students must submit a digital request form at least ________ hours in advance.', NULL, '24', 'A minimum 24-hour advance request is required.', 1),
(13, 2, 5, 'mcq', 'What is the minimum group size required to book a multimedia study pod in the East Wing?', '["2 students", "3 students", "4 students", "5 students"]', '3 students', 'The librarian states the group must have at least 3 registered students.', 1),
(14, 2, 6, 'fill_blank', 'The free weekly citation workshop on Wednesdays at 2:00 PM is held in Room ________.', NULL, '108', 'The librarian mentions the workshop takes place in Room 108.', 1),

(15, 3, 1, 'writing_task1', 'Task 1 (Academic - 150+ words): Summarize the renewable electricity generation trends in Germany, the UK, Australia, and Japan between 2010 and 2025, making relevant comparisons.', NULL, NULL, 'Ensure a clear overview paragraph identifying the upward trend across all four countries and highlighting Germany and the UK surpassing 50% by 2025.', 1),
(16, 3, 2, 'writing_task2', 'Task 2 (Essay - 250+ words): Many organizations now allow employees to work from home several days a week. Do the advantages of remote working for employees and society outweigh the disadvantages?', NULL, NULL, 'Address both advantages (commute savings, flexibility, reduced emissions) and disadvantages (isolation, blurred work-life boundaries) with a clear position throughout.', 2),

(17, 4, 1, 'speaking_part1', 'Part 1 (Introduction & Interview): Tell me about your hometown and how technology has changed the way people study or work there in recent years.', NULL, NULL, 'Aim for 3-5 natural sentences with specific examples and varied tenses.', 1),
(18, 4, 2, 'speaking_part2', 'Part 2 (Cue Card - 1 to 2 minutes): Describe a useful skill you learned outside of school or university. You should say: what the skill is, how and where you learned it, why you decided to learn it, and explain how this skill has helped you in your daily life.', NULL, NULL, 'Cover all four cue card prompts coherently using discourse markers and precise vocabulary.', 1),
(19, 4, 3, 'speaking_part3', 'Part 3 (Two-Way Discussion): Do you think artificial intelligence and online platforms will eventually replace traditional classroom teachers, or will human mentorship become even more valuable? Why?', NULL, NULL, 'Develop abstract arguments, compare perspectives, and justify your opinion with structured reasoning.', 1);

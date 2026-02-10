-- OPTIONAL: Run this ONLY if the user is missing on Production
-- Use phpMyAdmin to run this SQL query on the 'regsys_participant' database

INSERT INTO users (id, fname, lname, email, password, category, contactno, posting_date) 
VALUES (
    '202102734', 
    'Asma', 
    'Mohammed', 
    'asma.mohammed@placeholder.com', 
    '$2y$10$PlaceHolderHash', 
    'Participant',
    '971500000000',
    NOW()
);

-- NOTE: Please update the email and contact number with the correct values if known.

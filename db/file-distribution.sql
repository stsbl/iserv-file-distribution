/**
 * Author:  Felix Jacobi
 * Created: 28.11.2016
 * License: MIT license <https://opensource.org/licenses/MIT> 
 */

CREATE TABLE file_distribution (
    ID                  SERIAL          PRIMARY KEY,
    Title               TEXT            NOT NULL,
    Act                 TEXT            NOT NULL REFERENCES users(Act)
                                            ON UPDATE CASCADE
                                            ON DELETE CASCADE,
    IP                  INET            NOT NULL REFERENCES hosts(IP)
                                            ON UPDATE CASCADE
                                            ON DELETE CASCADE,
    ISOLATION           BOOLEAN         NOT NULL,
    UNIQUE(IP)
);

CREATE TABLE computer_sound_lock (
    ID                  SERIAL          PRIMARY KEY,
    IP                  INET            NOT NULL REFERENCES hosts(IP)
                                            ON UPDATE CASCADE
					    ON DELETE CASCADE    
);

GRANT USAGE, SELECT ON "file_distribution_id_seq", "computer_sound_lock_id_seq" TO "symfony";
GRANT SELECT ON "file_distribution", "computer_sound_lock" TO "symfony";

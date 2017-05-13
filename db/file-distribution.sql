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
    FolderAvailability  TEXT            NOT NULL CHECK (FolderAvailability IN
        ('readonly', 'replace', 'keep')),
    ISOLATION           BOOLEAN         NOT NULL,
    UNIQUE(IP)
);

CREATE TABLE file_distribution_rooms (
    ID                  SERIAL          PRIMARY KEY,
    Room		TEXT		NOT NULL
                                        REFERENCES rooms(Name)
                                            ON DELETE CASCADE
                                            ON UPDATE CASCADE
);

CREATE UNIQUE INDEX file_distribution_rooms_room_key ON file_distribution_rooms (Room);

CREATE TABLE computer_sound_lock (
    ID                  SERIAL          PRIMARY KEY,
    IP                  INET            NOT NULL REFERENCES hosts(IP)
                                            ON UPDATE CASCADE
					    ON DELETE CASCADE,    
    Act                 TEXT            NOT NULL REFERENCES users(Act)
                                            ON UPDATE CASCADE
				            ON DELETE CASCADE
);

GRANT USAGE, SELECT ON "file_distribution_id_seq", "computer_sound_lock_id_seq", "file_distribution_rooms_id_seq" TO "symfony";
GRANT SELECT ON "file_distribution", "computer_sound_lock" TO "symfony";
GRANT SELECT, INSERT, UPDATE, DELETE ON "file_distribution_rooms" TO "symfony";

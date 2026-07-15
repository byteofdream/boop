<?php
// To add a new feature, add a new entry to the $all array in run_migrations().
// Use a unique id like 'yourname_tablename' and your CREATE TABLE / ALTER TABLE SQL.
// It will auto-apply for everyone on the next page load.

function run_migrations($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS _migrations (
        id VARCHAR(64) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        applied_at INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $result = $conn->query("SELECT id FROM _migrations");
    $applied = [];
    while ($row = $result->fetch_assoc()) {
        $applied[$row['id']] = true;
    }

    $all = [
        'core_users' => [
            'name' => 'users table',
            'sql' => "CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(32) PRIMARY KEY,
                username VARCHAR(30) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at INT NOT NULL,
                bio TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],
        'core_posts' => [
            'name' => 'posts table',
            'sql' => "CREATE TABLE IF NOT EXISTS posts (
                id VARCHAR(32) PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                content TEXT NOT NULL,
                author VARCHAR(30) NOT NULL,
                tags TEXT DEFAULT NULL,
                upvotes INT DEFAULT 0,
                downvotes INT DEFAULT 0,
                created_at INT NOT NULL,
                INDEX idx_author (author),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],
        'core_comments' => [
            'name' => 'comments table',
            'sql' => "CREATE TABLE IF NOT EXISTS comments (
                id VARCHAR(32) PRIMARY KEY,
                post_id VARCHAR(32) NOT NULL,
                author VARCHAR(30) NOT NULL,
                content TEXT NOT NULL,
                created_at INT NOT NULL,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                INDEX idx_post (post_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],
        'core_votes' => [
            'name' => 'votes table',
            'sql' => "CREATE TABLE IF NOT EXISTS votes (
                post_id VARCHAR(32) NOT NULL,
                username VARCHAR(30) NOT NULL,
                vote_type ENUM('up', 'down') NOT NULL,
                PRIMARY KEY (post_id, username),
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],
        'kvs_achievements' => [
            'name' => 'add last_level to users',
            'sql' => "ALTER TABLE users ADD COLUMN last_level INT DEFAULT 0",
        ],
    ];

    foreach ($all as $id => $m) {
        if (!isset($applied[$id])) {
            if ($conn->query($m['sql']) === false) {
                error_log("Migration $id failed: " . $conn->error);
                continue;
            }
            $stmt = $conn->prepare("INSERT INTO _migrations (id, name, applied_at) VALUES (?, ?, ?)");
            $time = time();
            $stmt->bind_param('ssi', $id, $m['name'], $time);
            $stmt->execute();
        }
    }
}

-- 先刪除所有表格（如果存在）
DROP TABLE IF EXISTS post_comments;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS blocks;
DROP TABLE IF EXISTS post_tags;

-- 建立 users 表格
CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    role ENUM('student', 'teacher', 'admin') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 建立 categories 表格
CREATE TABLE categories (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (id),
    KEY idx_user_id (user_id),
    CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 建立 posts 表格
CREATE TABLE posts (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT,
    title VARCHAR(255),
    post_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_locked BOOLEAN DEFAULT FALSE,
    is_private BOOLEAN DEFAULT FALSE,
    allow_teacher_view BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (id),
    KEY idx_user_id (user_id),
    KEY idx_category_id (category_id),
    CONSTRAINT fk_posts_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_posts_category_id FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 建立 blocks 表格
CREATE TABLE blocks (
    id INT NOT NULL AUTO_INCREMENT,
    post_id INT NOT NULL,
    block_type ENUM('text', 'image', 'code', 'quote') NOT NULL,
    content TEXT,
    block_order INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_post_id (post_id),
    CONSTRAINT fk_blocks_post_id FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 建立 post_tags 表格
CREATE TABLE post_tags (
    id INT NOT NULL AUTO_INCREMENT,
    post_id INT NOT NULL,
    tag VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY post_tag (post_id, tag),
    CONSTRAINT fk_tags_post_id FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 建立 post_comments 表格
CREATE TABLE post_comments (
    id INT NOT NULL AUTO_INCREMENT,
    post_id INT NOT NULL,
    teacher_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_post_id (post_id),
    KEY idx_teacher_id (teacher_id),
    CONSTRAINT fk_comments_post_id FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_teacher_id FOREIGN KEY (teacher_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入初始用戶數據
INSERT INTO users (username, password, email, role) VALUES
('user', 'user', 'user@unitrack.dev', 'student'),
('teacher', 'teacher', 'teacher@unitrack.dev', 'teacher'),
('admin', 'admin', 'admin@unitrack.dev', 'admin'); 
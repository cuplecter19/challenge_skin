-- 이 SQL은 "게시판 write 테이블"에 컬럼을 추가합니다.
-- bo_table=fic 라면 보통 테이블은 g5_write_fic 입니다.
-- 실제 테이블명은 data/dbconfig.php의 table_prefix 확인 후 결정하세요.

-- 1) 컬럼 추가 (이미 있으면 에러가 날 수 있으니, 가능하면 phpMyAdmin에서 컬럼 존재 여부 확인 후 실행하세요)
ALTER TABLE `avo_write_fic`
  ADD COLUMN `wr_type` varchar(20) NOT NULL DEFAULT '' AFTER `wr_10`,
  ADD COLUMN `wr_date` varchar(10) NOT NULL DEFAULT '' AFTER `wr_subject`,
  ADD COLUMN `wr_done` text NOT NULL AFTER `wr_date`,
  ADD COLUMN `wr_done_count` int NOT NULL DEFAULT 0 AFTER `wr_done`,
  ADD COLUMN `wr_goal_total` int NOT NULL DEFAULT 0 AFTER `wr_done_count`,
  ADD COLUMN `wr_done_rate` tinyint NOT NULL DEFAULT 0 AFTER `wr_goal_total`;

-- 2) 인덱스 추가(달력/연속일/달성일 조회 성능용)
CREATE INDEX `idx_wr_date_type_done_rate`
  ON `avo_write_fic` (`wr_date`, `wr_type`, `wr_is_comment`, `wr_done_rate`);

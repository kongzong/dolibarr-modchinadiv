# modChinaDiv — Dolibarr 中国行政区划模块

Dolibarr 22.0.x 外部模块：中国行政区划标准数据（国家统计局 6 位编码）与工具库，
零 core 修改。作为物流、短信、电商订单等中国生态模块的公共依赖。

## 功能

- `llx_chinadiv_division` 表 + **3432 条全量数据**（31 省 + 港澳台 / 342 市 / 3056 区县），启用模块即自动载入
- 管理页：数据统计 + 上传 `pca-code.json` 导入/刷新（幂等，ON DUPLICATE KEY UPDATE）
- 工具函数：`chinadiv_get_divisions($parent)`、`chinadiv_find_by_name($name)`、`chinadiv_format_address(...)`（自动跳过"市辖区"，支持名称或编码混用）
- REST API：`GET /api/index.php/chinadiv/divisions?parent=44xxxx` 或 `?q=广州`（需 `chinadiv read` 权限）
- 权限：`chinadiv read` / `chinadiv admin`（导入）

## 安装

```
git clone <repo> htdocs/custom/chinadiv
```
Dolibarr → 设置 → 模块/应用 → 搜索 "ChinaDiv" → 启用（自动建表并载入全量数据）。

## 数据源与许可

数据来自 [modood/Administrative-divisions-of-China](https://github.com/modood/Administrative-divisions-of-China)（MIT，国家统计局编码），港澳台为手工补充。每年统计局编码更新后，从该仓库下载 `pca-code.json` 在管理页导入刷新。

## 测试

```
php tests/run_all.php
```

## V0.2 规划

表单级联选择器（Hook 注入第三方/联系人表单）、街道第四级、地址规范化工具。见 `docs/spec-chinadiv-v0.1.md`。

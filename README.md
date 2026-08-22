# modChinaDiv — Dolibarr 中国行政区划模块

Dolibarr 22.0.x 外部模块：中国行政区划标准数据（国家统计局 6 位编码）、
第三方公司区划结构化存储、表单级联选择器与工具库，零 core 修改。
作为物流、短信、电商订单等中国生态模块的公共依赖。

当前版本：**0.3.0**

## 功能

### 数据
- `llx_chinadiv_division`：**3350 条全量数据**（34 省含港澳台 / 342 市 / 2974 区县，
  国家统计局 6 位编码），启用模块即自动载入
- 管理页：数据统计 + 上传 `pca-code.json` 导入/刷新（幂等；统计局年度编码更新时用）
- 说明：东莞/中山等直筒子市无区县级，其 7 位镇级编码不在 V0.1+ 范围

### 表单级联（V0.2）
- 第三方公司、联系人的创建/编辑表单自动出现 **省→市→区县** 级联选择器
- 选中即回填 Dolibarr 标准字段：省份下拉（按名称匹配）+ 城市字段（市 区，自动跳过"市辖区"）

### 结构化存储（V0.3）
- `llx_chinadiv_soc_division`：第三方公司的省/市/区县**编码**（fk_soc 唯一），
  由模块 Trigger 在客户创建/保存时落库，编辑页自动回填
- 机器可读编码是物流（派送范围）、短信（归属地）等下游模块的依赖前提

### 库与 API
- 工具函数：`chinadiv_get_divisions($parent)`、`chinadiv_find_by_name($name)`、
  `chinadiv_format_address(...)`、`chinadiv_get_soc_codes($fkSoc)`
- REST API：
  - `GET /api/index.php/chinadiv/divisions?parent=440000`（子区划）或 `?q=广州`（名称搜索）
  - `GET /api/index.php/chinadiv/divisions/soc/{socid}`（第三方的区划编码，需 `societe lire` + `chinadiv read`）
- 权限：`chinadiv read` / `chinadiv admin`（导入）

## 安装

```
git clone <repo> htdocs/custom/chinadiv
```
Dolibarr → 设置 → 模块/应用 → 搜索 "ChinaDiv" → 启用（自动建两张表并载入全量数据）。

## 数据源与许可

数据来自 [modood/Administrative-divisions-of-China](https://github.com/modood/Administrative-divisions-of-China)
（MIT，国家统计局编码），港澳台为手工补充。每年统计局编码更新后，从该仓库下载
`pca-code.json` 在管理页导入刷新。

## 测试

```
php tests/run_all.php        # 结构测试（无 PHPUnit 环境可跑）
```

## 开发约定

本模块遵循 [custom/DOLIBARR-MODULE-DEVELOPMENT.md](../DOLIBARR-MODULE-DEVELOPMENT.md)
中的最佳实践与 AI 协作红线；本机环境、模块状态与交接信息见
[custom/AGENTS.md](../AGENTS.md)。规格与 ADR 见 `docs/`。

## 后续方向

街道/乡镇第四级（按需加，需评估安装体积）、地址规范化工具（存量自由文本地址结构化）、
联系人级联的编码存储（当前联系人只做文本回填，不做编码落库）。

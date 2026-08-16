# modChinaDiv V0.1 开发规格

## 1. 目的

为 Dolibarr 提供中国行政区划（省/市/区县）标准数据与工具，作为其他中国生态模块
（物流、短信、电商订单同步）的公共依赖。遵循 modWeCom 已验证的开发模式：
零 core 修改、分阶段、每阶段可验证、测试沉淀。

## 2. 范围

### V0.1 做

1. 区划数据表 `llx_chinadiv_division`（国家统计局 6 位编码 + 层级 + 父编码）
2. 省级 34 条数据内置（模块 SQL 种子）
3. 市/区县数据导入工具（管理页上传 pca-code.json，或 CLI 脚本）
4. 工具函数库：按父级取子区划、名称模糊查找、中文地址拼接
5. 与 core 字典的映射关系说明（省 code_region 901-934 ↔ GB 编码），V0.1 只读对照，不回写字典
6. REST API：GET /chinadiv/divisions?parent=、?q=
7. 权限：chinadiv read / admin
8. PHPUnit 风格测试 + run_all 运行器（复用 wecom 模式）

### V0.1 不做

- 表单级联选择器注入（Hook，V0.2）
- 街道/乡镇第四级（数据量大，V0.2 按需）
- 往 llx_c_departements 回写市级数据（评估 hook 需求后再定）
- 历史地址数据规范化工具（V0.2）

## 3. 数据规范

- 编码：国家统计局 6 位行政区划代码（如 110108 朝阳区），不用民政部 9 位（V0.1 简化，6 位已是事实标准且数据源丰富）
- 层级：1=省 2=市 3=区县；直辖市：市辖区层（如 1101）保留为 level 2 但标记"市辖区"名
- 港澳台：纳入（81/82/83），无下级
- 数据源：modood/Administrative-divisions-of-China（统计局编码，MIT 协议，来源与许可证记录在数据文件头部）

## 4. 表结构

```text
llx_chinadiv_division
rowid, code VARCHAR(6) UNIQUE, level TINYINT, parent_code VARCHAR(6) NULL,
name VARCHAR(64), short_name VARCHAR(32), active TINYINT DEFAULT 1,
date_creation, tms
```

索引：code UNIQUE、(parent_code, level)

## 5. 红线（继承 modWeCom §51）

- 不修改 htdocs/core 任何文件
- 不往 llx_c_* 字典表写数据（V0.1；V0.2 若需要须先写 ADR）
- 所有数据库操作经过模块 DAO
- 外部输入必须验证

## 6. 验收标准

1. 启用模块自动建表并载入 34 省级数据
2. 管理页导入 pca-code.json 后市/区县数据完整（约 3400 区县）
3. 重复导入幂等（更新而非重复插入）
4. `GET /api/index.php/chinadiv/divisions?parent=44` 返回广东下属市
5. 测试全过

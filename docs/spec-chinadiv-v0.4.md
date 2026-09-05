# modChinaDiv V0.4 开发规格（联系人编码存储 + 地址规范化）

V0.3 已实现第三方的区划编码结构化存储。V0.4 收尾 README 列出的两个待办：
联系人编码存储（当前联系人只做文本回填）、地址规范化工具（存量自由文本地址结构化）。

## 1. 范围

### V0.4 做

1. **联系人编码存储**
   - 新表 `llx_chinadiv_contact_division`（fk_socpeople 唯一，province/city/district 三个 6 位编码列）
   - DAO：`upsertContactCodes()` / `getContactCodes()`（与 soc 版本同语义：全空即删行）
   - Trigger 扩展：`CONTACT_CREATE` / `CONTACT_MODIFY` 读取与第三方相同的 POST 隐藏字段落库，
     失败仅记日志不阻断业务（继承 V0.3 约定）
   - AJAX 端点 `ajax/contact_division.php?fk_socpeople=N`：返回联系人编码，
     权限 `societe contact lire` + `chinadiv read`
   - JS 预填：
     - 联系人编辑页（`/contact/card.php?id=N`）：取联系人已存编码回填级联
     - 联系人创建页（`/contact/card.php?action=create&socid=X`）：取父第三方的编码预填
   - REST API：`GET divisions/contact/{id}`（权限 `chinadiv read` + `societe contact lire`）

2. **地址规范化工具**
   - 库函数 `chinadiv_parse_address($text)`：自由文本地址 →
     `{province_code, city_code, district_code, detail}`（贪心前缀匹配省→市→区县；
     解析不出省时返回 `null`；不写库、纯函数）
   - 管理页新增"地址规范化"区块：扫描 `societe.address` 非空但无编码记录的第三方，
     逐条展示解析预览（名称 + 地址 + 解析结果），支持"应用到全部"
     （逐条 upsert 到 `llx_chinadiv_soc_division`，只写模块自有表，删除行即可完全回退）
   - 应用动作权限：`chinadiv admin`（与导入同级）

### V0.4 不做

- 街道/乡镇第四级（数据量与选择器复杂度，继续按需推迟）
- 联系人地址的批量规范化（V0.4 只做第三方存量；联系人随表单选择落库）
- 定时自动规范化（Cron）——规范化结果需人工确认，不静默改数据
- 解析不出区县时的模糊/别名容错（"北京"→110000 这类简称匹配 V0.4 不做，标准全称优先）

## 2. 数据规范

- 沿用 V0.3：编码为统计局 6 位码，全空选择 = 删除记录行（而非存 NULL 行）
- 联系人表与第三方表结构对称，不做外键（Dolibarr 惯例，业务层保证）

## 3. 红线（继承 V0.1 §5）

- 零 core 修改；Trigger / 表单注入失败不得阻断业务
- 批量应用只写 `llx_chinadiv_soc_division`（模块自有表），不改 `llx_societe` 本体
- 所有输入经 GETPOST 类型过滤，编码列校验 6 位数字

## 4. 验收标准

1. 联系人编辑表单选择级联并保存后，`llx_chinadiv_contact_division` 出现编码行；
   再次编辑自动回填
2. 从第三方 Tab 新建联系人时，级联自动预填该第三方的已存编码
3. `chinadiv_parse_address('广东省深圳市南山区科技园南路15号')` →
   440000/440300/440305 + detail；无省名前缀的城市地址返回 null
4. 管理页规范化工具：预览列表与实际解析一致；应用后重复执行幂等（列表变空）
5. `tests/run_all.php` 全过（新增断言：联系人表 SQL、DAO 方法、Trigger 事件、
   ajax 权限检查、parse_address 纯函数行为）
6. 全模块 `php -l` 通过

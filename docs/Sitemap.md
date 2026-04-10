# 站点地图 (Sitemap) 生成功能

系统提供生成标准 `sitemap.xml` 的功能，该文件有助于搜索引擎（如百度、Google、Bing 等）更好地抓取和收录您的网站。

## 更新方式

系统支持以下三种方式更新 `sitemap.xml`，您可以根据自己的需求选择合适的方式：

### 方式一：命令行手动执行更新

当您需要立即生成或更新站点地图时，可以在项目根目录下执行以下 ThinkPHP 命令：

```bash
php think sitemap:generate
```

> **提示：** 命令执行成功后，站点地图会生成至网站 `public/sitemap.xml`。您可以通过 `https://您的域名/sitemap.xml` 访问该文件。

### 方式二：通过服务器定时任务（Cron）自动更新

为了保持站点地图的持续更新，建议配置服务器定时任务，每天自动执行一次生成指令。

以 Linux 系统为例，在服务器终端运行 `crontab -e`，添加如下配置（每天凌晨 2 点执行）：

```bash
0 2 * * * cd /您的项目路径/ && php think sitemap:generate >> /dev/null 2>&1
```

> **注意：** 请将 `/您的项目路径/` 替换为您服务器上该项目的实际绝对路径（例如 `/Users/miinno/workspace/code_file/php/tool.phpers.xyz`）。

### 方式三：通过网页接口请求更新（需管理员权限）

如果您不想操作服务器终端，或者希望未来在管理后台点击按钮直接更新，系统提供了一个受管理员登录态保护的 API 接口：

- **请求方式：** `GET`
- **请求地址：** `http://您的域名/master/system/sitemap`

当您使用已登录管理员账号的浏览器访问该地址时，系统会实时读取已启用的插件信息，生成最新的 `sitemap.xml` 文件，并在页面返回 JSON 格式的成功提示信息。
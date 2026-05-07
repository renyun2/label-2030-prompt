# 活动管理系统

一个基于 PHP 8.0 + MySQL 的现代化活动创建与管理系统。

## ✨ 功能特性

### 后台管理
- **网站基础信息管理**: 配置网站名称、描述、关键词等
- **分类管理**: 支持一级分类和二级分类的创建与管理
- **活动创建**: 
  - 选择一级分类和二级分类（可选）
  - 活动名称、介绍、海报上传
  - 按钮类型：链接跳转按钮 / 复制按钮
  - 活动开始和结束时间（精确到分钟）
- **活动列表**:
  - 拖拽排序功能
  - 活动编辑
  - 显示活动名称、分类、是否过期等详情

### 前台展示
- 现代化卡片式活动展示
- 分类筛选功能
- 活动信息完整展示（海报、名称、介绍、时间、按钮）
- 响应式设计，支持移动端

## 🚀 快速开始

### 环境要求
- Docker
- Docker Compose

### 安装步骤

1. **克隆项目**
```bash
git clone <repository-url>
cd label-2030
```

2. **启动 Docker 容器**
```bash
docker-compose up -d --build
```

3. **等待服务启动** (大约30秒)
   - MySQL 服务会自动初始化数据库
   - PHP 服务会在首次访问时自动创建数据表

4. **访问系统**
   - 前台: http://localhost:8080
   - 后台: http://localhost:8080/admin/login.php

### 默认账号
- 用户名: `admin`
- 密码: `admin123` //初始化数据库时配置为准

## 📁 项目结构

```
label-2030/
├── docker-compose.yml          # Docker Compose 配置
├── Dockerfile                  # PHP 容器配置
├── nginx/
│   └── default.conf           # Nginx 配置
├── src/                       # 源代码目录
│   ├── index.php             # 前台首页
│   ├── init.php              # 数据库初始化
│   ├── common.php            # 公共函数
│   ├── config/
│   │   └── database.php      # 数据库配置
│   ├── core/
│   │   └── Database.php      # 数据库类
│   ├── admin/                # 后台管理
│   │   ├── login.php         # 登录页面
│   │   ├── index.php         # 后台首页
│   │   ├── settings.php      # 网站设置
│   │   ├── categories.php    # 分类管理
│   │   ├── events.php        # 活动列表
│   │   ├── event-create.php  # 创建活动
│   │   ├── event-edit.php    # 编辑活动
│   │   ├── logout.php        # 退出登录
│   │   ├── layout/           # 布局文件
│   │   └── api/              # API接口
│   ├── assets/               # 静态资源
│   │   ├── admin.css         # 后台样式
│   │   └── frontend.css      # 前台样式
│   └── uploads/              # 上传文件目录
└── README.md                  # 项目说明
```

## 🗄️ 数据库配置

- **数据库名**: domo
- **用户名**: domo
- **密码**: 8dZnsYMbizSPzk6c
- **主机**: mysql (Docker 内部网络)

数据库会在首次访问网站时自动初始化，包含以下表：
- `site_settings` - 网站设置
- `categories` - 分类表
- `events` - 活动表
- `admins` - 管理员表

## 🎨 技术栈

- **后端**: PHP 8.0
- **数据库**: MySQL 8.0
- **Web服务器**: Nginx + PHP-FPM
- **容器化**: Docker + Docker Compose
- **前端**: 原生 HTML + CSS + JavaScript
- **UI库**: SortableJS (拖拽排序)

## 📝 使用说明

### 创建活动

1. 登录后台管理系统
2. 点击左侧菜单"创建活动"
3. 填写活动信息：
   - 选择一级分类（必填）
   - 选择二级分类（可选）
   - 输入活动名称
   - 填写活动介绍
   - 上传海报图片
   - 选择按钮类型：
     - **链接跳转**: 填写链接地址和按钮名称
     - **复制按钮**: 填写复制内容、按钮名称和复制成功提示
   - 设置活动开始和结束时间
4. 点击"创建活动"

### 管理分类

1. 进入"分类管理"页面
2. 添加一级分类
3. 在一级分类下可以添加二级分类
4. 删除分类时会检查是否有关联的子分类或活动

### 活动排序

在活动列表页面，可以通过拖拽活动行来调整显示顺序，前台会按照设置的顺序展示活动。

## 🔧 常用命令

```bash
# 启动服务
docker-compose up -d

# 查看日志
docker-compose logs -f

# 停止服务
docker-compose down

# 重启服务
docker-compose restart

# 进入 PHP 容器
docker exec -it label2030_php bash

# 进入 MySQL 容器
docker exec -it label2030_mysql bash
```

## 📦 目录权限

确保以下目录有写入权限：
```bash
chmod -R 755 src/uploads
```

## 🔐 安全建议

1. **修改默认管理员密码**: 首次登录后立即修改
2. **修改数据库密码**: 修改 docker-compose.yml 中的数据库密码
3. **生产环境部署**: 
   - 使用 HTTPS
   - 配置防火墙
   - 定期备份数据库

## 📞 支持

如有问题，请检查：
1. Docker 服务是否正常运行
2. 端口 8080 和 3306 是否被占用
3. 容器日志是否有错误信息

## 📄 许可

本项目仅供学习和参考使用。

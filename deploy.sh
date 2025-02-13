#!/bin/bash

# 检查是否传入了参数（图片目录）
if [ ! -z "$1" ]; then
    IMAGES_PATH="$1"
fi

# 检查配置文件是否已处理
if [ ! -f .env ]; then
    echo "fail: 未找到配置文件，请先运行 cp .env.example .env，并修改数据库等置后再运行此脚本"
    exit 1
fi

# 检查 PHP 是否安装
if [[ -x "$(command -v php)" &&  $(php -r "echo PHP_VERSION_ID;") -ge 80200 ]]; then
    # PHP版本符合要求，本地安装
    echo "PHP已安装且符合要求，执行本地安装"

    # 检查composer是否安装
    if [ ! -x "$(command -v composer)" ]; then
        echo "fail: Composer 未安装，请先自行安装 Composer"
        exit 1
    fi
    echo "正在安装composer依赖"
    set -e  # 遇到错误自动退出
    composer install
    echo "✅ composer install 执行成功"

    # 检查.env中APP_KEY是否已设置，如未设置则生成
    if [ -z "$(grep '^APP_KEY=' .env | cut -d'=' -f2)" ]; then
    echo "正在生成APP_KEY"
        php artisan key:generate
    fi

    # 迁移数据库
    echo "执行数据表创建"
    php artisan migrate

    # 检查public/storage目录是否存在，不存在则创建
    if [ ! -d public/storage ]; then
        php artisan storage:link
    fi

    # 创建软链接到图片目录
    if [ ! -z "$IMAGE_PATH"  ]; then
        echo "正在创建软链接到图片目录"
        ln -s $IMAGE_PATH storage/ai_images
    elif [ "${LARAVEL_SAIL}" != "1" ] && [ ! -L "storage/ai_images/demo" ]; then
        echo "默认将demo文件夹软链接到图片目录"
         ln -s "${PWD}/demo" storage/ai_images
    fi

    # 同步图片
    echo "同步图片目录中的jpeg文件到数据库中"
    php artisan sync:images --all

    # 定义定时任务
    CRON_JOB="* * * * * cd ${PWD} && $(command -v php) artisan schedule:run >> /dev/null 2>&1"
    if [ -x "$(command -v composer)" ]; then
        # 检查定时任务是否已经存在,不存在则添加到crontab中
        (crontab -l 2>/dev/null | grep -F "$CRON_JOB") || (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    fi

    # 启动服务
    if [ "${LARAVEL_SAIL}" != "1" ]; then
        php artisan serve --port=80
    fi
    echo "服务已启动"
elif [ -x "$(command -v docker)" ]; then
    # PHP版本不符合要求，使用docker安装
    echo "未安装 PHP 或版本不符合要求，检测到docker，使用docker安装"

    # 检查docker是否运行
    if pgrep -x "dockerd" > /dev/null || docker info >/dev/null 2>&1; then
        echo "Docker is running"
    else
        echo "fail: Docker 未运行，请先启动Docker服务"
        exit 1
    fi

    # 检查sail是否安装
    if [ ! -f ./vendor/bin/sail ] || [ ! -x ./vendor/bin/sail ]; then
        echo "正在构建composer镜像"
        # 先使用composer镜像安装sail
        docker run --rm \
               --pull=always \
               -v "$(pwd)":/opt \
               -w /opt \
               composer \
               bash -c "composer install"
        if [ ! -f ./vendor/bin/sail  ]; then
            echo "composer安装sail失败，请检查网络并重试"
            exit 1
        fi
    fi

    # 构建业务镜像
    export $(grep -v '^#' .env | xargs) && docker-compose up --build -d
    echo "容器已启动，正在执行部署脚本（请确保docker-compose版本在2.x）"
    docker-compose exec laravel.test bash deploy.sh

else
    echo "fail: 未检测到php8.2或docker，请先自行安装一种"
fi

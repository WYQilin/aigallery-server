<!-- PROJECT LOGO -->
<br />

[![Laravel](https://img.shields.io/badge/Laravel-11.x-brightgreen.svg)](https://laravel.com)
[![License](https://img.shields.io/github/license/WYQilin/aigallery.svg)](https://github.com/WYQilin/aigallery/blob/main/LICENSE)
[![GitHub stars](https://img.shields.io/github/stars/WYQilin/aigallery.svg?style=social&label=Stars)](https://github.com/WYQilin/aigallery)

<!-- PROJECT LOGO -->
<p align="center">
  <a href="https://github.com/WYQilin/aigallery-server">
    <img src="demo/logo.png" alt="Logo" width="80" height="80">
  </a>

  <h3 align="center">🎨奇绘图册</h3>
  <p align="center">
    一款帮AI绘画爱好者维护绘图作品的小程序
    <br />
    <a href="https://github.com/WYQilin/aigallery-server#预览体验">查看Demo</a>
    ·
    <a href="https://github.com/WYQilin/aigallery-server/issues">反馈</a>
    ·
    <a href="https://blog.csdn.net/qq_37788558/article/details/145499404">Blog</a>
</p>

> 此项目为服务端部分，小程序端参见：[微信小程序](https://github.com/WYQilin/aigallery)
 
## 目录

- [项目介绍](https://github.com/WYQilin/aigallery-server?tab=readme-ov-file#%E9%A1%B9%E7%9B%AE%E4%BB%8B%E7%BB%8D)
- [预览体验](https://github.com/WYQilin/aigallery-server?tab=readme-ov-file#%E9%A2%84%E8%A7%88%E4%BD%93%E9%AA%8C)
    - [截图示例](#截图示例)
    - [在线体验](#在线体验)
- [功能介绍](https://github.com/WYQilin/aigallery-server?tab=readme-ov-file#%EF%B8%8F%E5%8A%9F%E8%83%BD%E4%BB%8B%E7%BB%8D)
- [安装部署](https://github.com/WYQilin/aigallery-server?tab=readme-ov-file#%E5%AE%89%E8%A3%85%E9%83%A8%E7%BD%B2)
  - [开发前的配置要求](#开发前的配置要求)
  - [快速开始](#快速开始)
- [联系作者](https://github.com/WYQilin/aigallery-server?tab=readme-ov-file#%E8%81%94%E7%B3%BB%E4%BD%9C%E8%80%85)
- [License](https://github.com/WYQilin/aigallery-server?tab=readme-ov-file#%EF%B8%8Flicense)
- [鸣谢](https://github.com/WYQilin/aigallery-server?tab=readme-ov-file#%E9%B8%A3%E8%B0%A2)

## 📖项目介绍
「奇绘图册」一款帮AI绘画爱好者记录和维护绘图作品的小程序。旨在让大家能够便捷的拥有一个个人画廊工具。

<div align="center">
    <img src="demo/overview.png">
</div>

完整的项目结构如图，其中**绿色部分**是目前开源的内容。**虚线部分**由于我的Mac M3带不动图生视频模型甚至flux也很吃力，所以本项目主要基于SD及WebUI实现，理论上都适用，但兼容性未做过多测试，可能存在问题，可自行解决或提issue。

**社交平台自动发布部分**不准备分享，因为喜闻乐见的一公布就容易被封禁失效，有兴趣的可以交流探讨，也可开动脑筋自行实现。

**群聊机器人部分**由于wechaty等群聊机器人年底全被封了，企业群聊机器人又只能加入组织后才能体验，展示demo十分不便，所以没有整理这部分，也暂不做讨论。后续整理完代码后开源，目前代码实现有点乱，感兴趣的同学可以先关注一波。

## 👀预览体验
### 截图示例

<div align="center">
    <img src="demo/screenshots.png">
</div>

### 在线体验

<div align="center">
    <img src="demo/qrcode.png" width="100" height="100">
</div>

- 由于视频类目不支持，demo中视频使用gif图片兼容
- 由于审核原因，只放了一点点普通图片
- 由于示例服务器带宽较低，可能加载稍慢

以上，敬请谅解～
> 您如果只是自用，且擦边内容较多，也可以考虑只发布到体验版，不上线到正式版。

## 🏷️功能介绍
### 小程序端
如上面架构图中小程序部分所示，小程序部分主体分三个模块：图集、图池、视频。
- 图集：当提示词等参数不变时，生成的同一批图片视为一个图集，即`prompt_hash`相同的若干图片展示在一起，并展示对应的画图参数。
- 图池：展示指定目录中的全部图片。指定图片目录后，系统自动维护里边的图片信息，生成类似github贡献热力图，可按日期查阅不同日期的图片，见证自己的过往成就和风格变化。
- 视频：展示视频列表。可直接在图池页面选择多张图片自动合成简易的幻灯片式的轮播视频；也可使用文、图生视频的结果（作者Mac M3带不动图生视频，可以自己完善）

### 服务端
- 服务端通过定时任务更新目录中的图片数据，也可主动调用指令。（`php artisan sync:images`）
- 数据存储在mysql，图片文件可以选择存储在本地磁盘(默认)或对象存储服务，只需调整.env中的`FILESYSTEM_DISK=s3`，并设置AWS_相关配置。
- 如果图片文件存储在本地磁盘，可以通过nginx的`http_image_filter_module`模块压缩图片，节省服务器带宽（小程序封面展示不需要太高清）约**10**倍；有条件的可以使用对象储存服务，配合缩略服务和CDN，资源加载速度更快。
<div align="center">
    <img src="demo/size_compare.png" width="200" height="210">
</div>

- 通过ffmpeg可以实现简单的多张图片合并成视频，有条件的可以接入大模型的图生视频产物。
<div align="center">
    <img src="demo/image_merge.gif" width="100" height="140">
</div>

## 📦安装部署
### 开发前的配置要求
- 本项目后端基于`php`开发，使用当前最新的`laravel11`框架，依赖`php8.2`及以上版本。
- 数据库建议使用`mysql8.0`及以上版本。
- 图片拼接视频依赖`ffmpeg4.4`及以上版本。
- 您也可以选择通过docker快速部署demo。


### 快速开始

**🔔项目提供了一键部署脚本，可以直接运行`bash deploy.sh`（确保网络/代理畅通）**

会优先检测php版本符合要求执行本地部署；不符合要求时执行docker部署。

其中deploy.sh运行时支持图片路径参数即`bash deploy.sh 你的图片目录`，不指定路径时默认使用项目根目录的demo文件夹

```shell
git clone https://github.com/WYQilin/aigallery-server
cd aigallery-server
sh deploy.sh
# 或 sh depoly.sh 你的图片目录
```
三行指令即可完成安装，部署完成后到小程序查看即可。


### ~~手动安装~~
略，参考 deploy.sh 或 [blog](https://blog.csdn.net/qq_37788558/article/details/145499404)

### ~~使用docker安装~~
略，参考deploy.sh


### 📧联系作者
- 有问题和建议请提issue（首选）
- 通过小程序底部按钮可报bug和联系“客服”
- 可以关注我的[博客](http://xiaobaiqi.blog.csdn.net)并私信


### ©️License
 
该项目采用 [Apache-2.0 License](LICENSE) 授权许可，详情请参阅。

### 🔗鸣谢
- [Laravel](https://laravel.com)
- [StableDiffusion](https://stabledifffusion.com/)
- [~~七牛云~~]()（广告位：求一个对象存储赞助，demo的服务器带宽太小了，图片加载吃力。目前基于七牛云S3调通，可更换并在文档补充介绍）




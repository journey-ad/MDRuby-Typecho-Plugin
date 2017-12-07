# MDRuby-Typecho-Plugin
Typecho Markdown Ruby 标签拓展支持插件

[Demo](https://imjad.cn/archives/code/markdown-ruby-tag-extension-plugin-test)

![Screenshot](https://img.imjad.cn/images/2017/12/07/Screenshot-20171207133533-1552x850.png)
## 介绍
- 使用广受欢迎的 [Parsedown](https://github.com/erusev/parsedown) 作为基础解析库
- 通过简洁的语法在文章中书写标准的 ruby 标签
- 自动转换词汇表内的词汇到 ruby 标签
- 内置 ruby 标签的样式并可自由配置

[关于 ruby 标签](https://www.w3.org/International/articles/ruby/)

## 安装方法
Download ZIP, 解压，将 MDRuby-Typecho-Plugin-master 重命名为 MDRuby ，之后上传到你博客中的 `/usr/plugins` 目录，在后台启用即可

可前往设置界面选择是否启用词汇表和配置样式

## 使用方法
在文章里以下列语法书写即可

#### 语法说明
```
[原文]^(yuán wén)
[原文]^（yuán wén） 使用全角空格
[原文]（yuán wén） 使用全角空格
```

## Thanks

[parsedown-rubytext](https://github.com/noisan/parsedown-rubytext)

## LICENSE

MIT © [journey.ad](https://github.com/journey-ad/)

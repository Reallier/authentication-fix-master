#!/bin/bash
cd src-clientarea
pnpm build
cd ..
# 然后打包当前目录下的所有文件
tar -zcvf ../authentication.tar.gz \
    --exclude="src-clientarea" \
    --exclude="node_modules" \
    --exclude=".git" \
    --exclude=".idea" \
    ../authentication

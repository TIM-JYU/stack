#!/bin/bash
# Builds image of Stack
# ARG1: Build target (prod or dev).
# ARG2: Registry name to push image to.
# ARG3: Version tag to use. Use `build` if you don't want it tagged as latest.

target="$1"
case "$target" in
    "prod") tag_postfix="";;
    "dev")  tag_postfix="-dev";;
    *)  echo "Usage: build.sh (prod|dev) [tag] [docker_registry]"
        exit 1
esac

version_tag="$3"
DEV_TAG="build"
if [ -z "$version_tag" ]; then
    version_tag="$DEV_TAG"
fi
IMAGE_NAME="stack-api"
IMAGE_NAME_TAGGED="$IMAGE_NAME:$version_tag$tag_postfix"

docker build --target "$target" -t "$IMAGE_NAME_TAGGED" . || exit 1
echo "Build finished for $IMAGE_NAME_TAGGED"

registry="$2"
IMAGE_TAG_VERSIONED="$registry/$IMAGE_NAME_TAGGED"
IMAGE_TAG_LATEST="$registry/$IMAGE_NAME:latest$tag_postfix"
if [ -n "$registry" ]; then
    docker tag "$IMAGE_NAME_TAGGED" "$IMAGE_TAG_VERSIONED"
    docker push "$IMAGE_TAG_VERSIONED"
    echo "Pushed $IMAGE_TAG_VERSIONED"
    if [ "$version_tag" != "$DEV_TAG" ]; then
        docker tag "$IMAGE_NAME_TAGGED" "$IMAGE_TAG_LATEST"
        docker push "$IMAGE_TAG_LATEST"
        echo "Pushed $IMAGE_TAG_LATEST"
    fi
fi

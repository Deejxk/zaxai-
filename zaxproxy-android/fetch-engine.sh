#!/bin/sh
# Downloads the tunnel engine source at a known version into app/src/main/jni/.
set -e
DIR="$(dirname "$0")/app/src/main/jni/hev-socks5-tunnel"
REV=fc3f90040b8d4b537341c96538669a9b2e35d514
if [ ! -d "$DIR/.git" ]; then
  git clone https://github.com/heiher/hev-socks5-tunnel.git "$DIR"
fi
git -C "$DIR" fetch --quiet origin
git -C "$DIR" checkout --quiet "$REV"
git -C "$DIR" submodule update --init --recursive
echo "Engine ready at $DIR"

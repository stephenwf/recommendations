#!/usr/bin/env bash
set -ex

[ $(curl --write-out %{http_code} --silent --output /dev/null localhost/search) == 200 ]
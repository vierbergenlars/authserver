#!/usr/bin/env bash
if [[ ! -e "/usr/local/bin/ansible" ]]; then
    sudo apt-get install -y python python-pip python-dev \
     libyaml-0-2 python-crypto python-ecdsa python-httplib2 python-jinja2 \
     python-markupsafe python-paramiko python-selinux python-yaml

    sudo pip install ansible
fi


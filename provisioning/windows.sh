#!/usr/bin/env bash
if [[ ! -e "/etc/ansible/hosts" ]]; then
    sudo apt-get install -y python python-pip python-dev
    sudo pip install ansible
    mkdir /etc/ansible
    echo "localhost" >> /etc/ansible/hosts
    chmod 666 /etc/ansible/hosts
fi
ansible-playbook /vagrant/provisioning/playbook.yml --connection=local
Vagrant.configure(2) do |config|
    config.vm.box = "debian/stretch64"
    config.vm.network "private_network", ip: "192.168.80.2"

    config.vm.synced_folder ".", "/vagrant", type: "virtualbox"
    config.vm.synced_folder "src", "/var/www/src", create: true, type: "virtualbox"
    config.vm.synced_folder "plugins", "/var/www/plugins", create: true, type: "virtualbox"

    config.vm.provision :shell, path: "provisioning/install-ansible.sh"
    config.vm.provision :shell, inline: "PYTHONUNBUFFERED=1 sudo ansible-playbook /vagrant/provisioning/playbook.yml --connection=local"

    config.vm.provider "virtualbox" do |vb|
        vb.gui = false
        vb.cpus = 1
        vb.memory = 512
    end

end

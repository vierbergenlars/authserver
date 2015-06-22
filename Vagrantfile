Vagrant.configure(2) do |config|
    config.vm.box = "debian/wheezy64"
    config.vm.network "private_network", ip: "192.168.80.2"
    config.vm.synced_folder "src", "/var/www/src", create: true

    config.vm.provider "virtualbox" do |vb|
        vb.gui = false
        vb.cpus = 1
        vb.memory = 512
    end

    config.vm.provision "ansible" do |ansible|
        ansible.playbook = "provisioning/playbook.yml"
        ansible.raw_ssh_args = ['-o ControlMaster=no' ]
        ansible.sudo = true
    end
end

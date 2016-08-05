def which(cmd)
    exts = ENV['PATHEXT'] ? ENV['PATHEXT'].split(';') : ['']
    ENV['PATH'].split(File::PATH_SEPARATOR).each do |path|
        exts.each { |ext|
            exe = File.join(path, "#{cmd}#{ext}")
            return exe if File.executable? exe
        }
    end
    return nil
end

Vagrant.configure(2) do |config|
    config.vm.box = "debian/jessie64"
    config.vm.network "private_network", ip: "192.168.80.2"
    config.vm.synced_folder "src", "/var/www/src", create: true

    config.vm.provider "virtualbox" do |vb|
        vb.gui = false
        vb.cpus = 1
        vb.memory = 512
    end

    if which('ansible-playbook')
        config.vm.provision "ansible" do |ansible|
            ansible.playbook = "provisioning/playbook.yml"
            ansible.raw_ssh_args = ['-o ControlMaster=no' ]
            ansible.sudo = true
        end
    else
        config.vm.provision :shell, path: "provisioning/windows.sh"
    end
end

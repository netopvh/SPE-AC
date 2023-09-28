
# Sistema de Ponto Eletrônico

### 💻 Requisitos

**PHP:** 7.3

**MySql:** 10.6.x

**MongoDB:** 5.x

**LDAP**
### 🔧 Instalação

1. Crie um banco de dados e importe o arquivo spe_novo.sql.

2. Crie uma collection no MongoDB

3. Crie um servidor LDAP para acessar o sistema.

4. Altere as configurações dos bancos de dados no arquivo `config/database.php` _(Os dados da variavel **DATABASE_SEICT** são opcionais)_

5. Crie uma tarefa no cron para executar o arquivo `public/importacao.php` 1x por dia 

ex.: 0 0 * * * php /var/www/html/public/importacao.php


### 📌 Observações
    
As informações do sistema (Dados Funcionais, Férias, Atestados, Faltas, Órgãos, Lotações) são importados do banco de dados do sistema de RH.

Todos os códigos referente a importação dos dados do sistema de RH estão no arquivo `public/importacao.php`

A versão atual só permite acesso via **LDAP**

Se o valor da constante ambiente no arquivo `config/app.php` for igual a `local` ou `develop` sistema não verifica o LDAP e permite o acesso com qualquer senha.

### Comandos para ajuste de dependências

As dependências utilizadas necessitam do composer na versão 1.x, para isso é necessário retroceder a versão caso esteja utilizando uma versão superior a 1.x.

Execute o seguinte comando e posteriormente instale as dependências.

```
$ composer self-update --1
```
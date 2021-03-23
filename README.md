# Módulo de pesquisa [Parte 2 - Back]
Requisitos: PHP, Composer, Slim Framework e MySQL

### Clone o repositório
```
git clone https://github.com/renanramoslinhares/modulo_de_pesquisas_back
```

### Agora instale as dependências
```
cd modulo_de_pesquisas_back && composer install
```

### Insira suas credencias para permitir que o back end  interaja com o MySQL
Vá em `app/routes.php` e altere a função `configConnection()` // isto pode ser aperfeiçoado

### Importar banco de dados
Na raiz do projeto há um arquivo chamado `start.sql`. Você pode importá-lo manualmente ou rodando os seguintes comandos:

###### "Entre" no MySQL e insira sua senha (você pode utilizar outro nome de usuário)
`mysql -u root -p`
```
mysql> source ./start.sql;
```

### Ligar o servidor PHP na porta :8000
Feche o mysql e ative o servidor da API
```
php -S localhost:8000 -t public public/index.php
```
PRONTO.

Observação:
  É importante deixar os dois servideores ativos para navegar.
  Um sustenta o back-end, com PHP e outro, do front, roda com NODE.

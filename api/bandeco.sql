SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Table `bandeco`.`familias`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `bandeco`.`familias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Id da família' ,
  `nome` VARCHAR(100) NOT NULL COMMENT 'Nome da família' ,
  `nota` FLOAT NULL COMMENT 'Armazena a nota cacheada da família (NULL para sem nota)' ,
  `votos` INT UNSIGNED NOT NULL COMMENT 'Diz a quantidade de votos computados' ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `nome` (`nome` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = utf8
COMMENT = 'Armazena as famílias de pratos';


-- -----------------------------------------------------
-- Table `bandeco`.`pratos`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `bandeco`.`pratos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificação do prato' ,
  `familia` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Referencia a família do prato' ,
  `nome` VARCHAR(100) NOT NULL COMMENT 'Guarda o nome do prato' ,
  `nota` FLOAT NULL DEFAULT NULL COMMENT 'Armazena a nota cacheada da nota (NULL para sem nota)' ,
  `votos` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Diz a quantidade de votos computados' ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `nome` (`nome` ASC) ,
  INDEX `familia` (`familia` ASC) ,
  CONSTRAINT `familiaP`
    FOREIGN KEY (`familia` )
    REFERENCES `bandeco`.`familias` (`id` )
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 167
DEFAULT CHARACTER SET = utf8
COMMENT = 'Guarda os diferentes pratos';


-- -----------------------------------------------------
-- Table `bandeco`.`refeicoes`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `bandeco`.`refeicoes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Id da refeição' ,
  `data` DATETIME NOT NULL COMMENT 'Armazena a data da refeição' ,
  `prato` INT UNSIGNED NOT NULL COMMENT 'Referencia o prato da refeição' ,
  `guarnicao` VARCHAR(100) NOT NULL COMMENT 'Guarda a guarnição da refeição' ,
  `pts` VARCHAR(100) NOT NULL COMMENT 'Guarda o acompanhamento do PTS' ,
  `salada` VARCHAR(100) NOT NULL COMMENT 'Guarda a sala da refeição' ,
  `sobremesa` VARCHAR(100) NOT NULL COMMENT 'Guarda a sobremesa da refeição' ,
  `suco` VARCHAR(100) NOT NULL COMMENT 'Guarda o suco da refeição' ,
  `nota` FLOAT NULL DEFAULT NULL COMMENT 'Guarda o cache pré calculada da nota (NULL para 0 votos)' ,
  `votos` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Guarda o número de votos' ,
  `proxima` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Referencia a próxima refeição' ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `dia` (`data` ASC) ,
  INDEX `prato_idx` (`prato` ASC) ,
  UNIQUE INDEX `refeicao_idx` (`proxima` ASC) ,
  CONSTRAINT `pratoR`
    FOREIGN KEY (`prato` )
    REFERENCES `bandeco`.`pratos` (`id` )
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `refeicaoR`
    FOREIGN KEY (`proxima` )
    REFERENCES `bandeco`.`refeicoes` (`id` )
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 403
DEFAULT CHARACTER SET = utf8
COMMENT = 'Armazena todas as refeições';


-- -----------------------------------------------------
-- Table `bandeco`.`votos`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `bandeco`.`votos` (
  `refeicao` INT UNSIGNED NOT NULL COMMENT 'Refeição associada' ,
  `ra` INT UNSIGNED NOT NULL COMMENT 'RA do votante' ,
  `data` DATETIME NOT NULL COMMENT 'Data da votação' ,
  `voto` TINYINT NOT NULL COMMENT 'Valor do voto (-2 a 2)' ,
  INDEX `refeicao_idx` (`refeicao` ASC) ,
  PRIMARY KEY (`refeicao`, `ra`) ,
  CONSTRAINT `refeicaoV`
    FOREIGN KEY (`refeicao` )
    REFERENCES `bandeco`.`refeicoes` (`id` )
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Armazena todos os votos enviados';


-- -----------------------------------------------------
-- Table `bandeco`.`ouvintes`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `bandeco`.`ouvintes` (
  `ra` INT UNSIGNED NOT NULL COMMENT 'RA do usuário' ,
  `email` VARCHAR(100) NOT NULL COMMENT 'E-mail para o qual as avisos serão enviados' ,
  `nome` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Nome do usuário' ,
  `avisos` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Avisos que serão enviados para o email (1=semana,2=ruim,4=bom)' ,
  `chave` BINARY(20) NOT NULL COMMENT 'Chave secreta para alteração do registro' ,
  PRIMARY KEY (`ra`) ,
  UNIQUE INDEX `email` (`email` ASC) ,
  UNIQUE INDEX `chave` (`chave` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Armazena os ouvintes registrados pelos usuários';


-- -----------------------------------------------------
-- Table `bandeco`.`avisos`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `bandeco`.`avisos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Id do aviso' ,
  `tipo` TINYINT UNSIGNED NOT NULL COMMENT 'Tipo do aviso' ,
  `refeicao` INT UNSIGNED NOT NULL COMMENT 'Faz referência à refeição que gerou o aviso' ,
  `data` DATETIME NOT NULL COMMENT 'Indica quando o aviso foi gerado' ,
  INDEX `refeicao_idx` (`refeicao` ASC) ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `refeicaoA`
    FOREIGN KEY (`refeicao` )
    REFERENCES `bandeco`.`refeicoes` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Armazena os avisos gerados';



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

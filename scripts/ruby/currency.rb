=begin
https://github.com/freenode-anime/enju/blob/master/plugins/currency.rb
thanks to jnylen

exec:~forex|10|0|1|1|||||ruby scripts/ruby/currency.rb %%trailing%%

# apt-get install ruby
# gem install money
# gem install google_currency

$ ruby currency.rb 'usd 10 aud'

=end

require 'money'
require 'money/bank/google_currency'

Money.use_i18n = false

Money::Bank::GoogleCurrency.ttl_in_seconds = 86400
Money.default_bank = Money::Bank::GoogleCurrency.new

trailing, *the_rest = ARGV

parts = trailing.split(" ")

from_str = parts[0]
amount_str = parts[1]
to_str = parts[2]

money = Money.new(amount_str.to_i * 100, from_str.upcase)
result = money.exchange_to(to_str.upcase.to_sym)

puts result

<template>
  <GoodsBox :image="image" :name="name" :name-tag="gtype" :link="'/integral/goods/detail/' + id">
    <div slot="tag" class="tags">
      <div class="tag" v-if="showStrong(exchange)">
        <span class="strong">{{exchange}}</span>
        <span class="txt">{{exchangeText}}</span>
      </div>
      <div class="tag" v-if="showStrong(price)">
        <span class="symbol" v-if="showStrong(exchange)">+</span>
        <span class="strong">{{price | yuan}}</span>
      </div>
    </div>
  </GoodsBox>
</template>

<script>
import GoodsBox from "@/components/GoodsBox";
export default {
  data() {
    return {};
  },
  props: {
    id: [String, Number],
    image: String,
    name: String,
    exchange: [String, Number],
    exchangeText: {
      type: String,
      default: function() {
        return this.$store.state.member.memberSetText.point_style;
      }
    },
    price: [String, Number],
    sales: [String, Number],
    gtype: String
  },
  methods: {
    showStrong(value) {
      return parseFloat(value) > 0;
    }
  },
  components: {
    GoodsBox
  }
};
</script>
<style scoped>
.tags {
  display: flex;
  overflow: hidden;
  white-space: nowrap;
}

.tags .txt {
  color: #666;
  font-size: 12px;
}

.tag .symbol {
  padding: 0 4px;
}

.tag .strong {
  color: #ff454e;
  font-weight: 800;
}
</style>

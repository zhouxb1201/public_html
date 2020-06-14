<template>
  <div class="catalog">
    <van-cell-group v-if="data > 0">
        <van-cell v-for="(item,index) in source" :key="index" :to="'/course/detail/'+ data+'/'+item.knowledge_payment_id">
          <van-col span="18">{{item.knowledge_payment_name}}</van-col>
          <van-col span="6" v-if="!is_buy" style="text-align:right">
                <van-tag
                  class="tag"
                  round
                  size="medium"
                  color="#FAE9E6"
                  text-color="#ff454e"
                  v-if="item.knowledge_payment_is_see == -1"
                >付费浏览</van-tag>
                <van-tag
                  class="tag"
                  round
                  size="medium"
                  color="#FAE9E6"
                  text-color="#ff454e"
                  v-if="item.knowledge_payment_is_see > 0"
                >试学</van-tag>
          </van-col>
          <!--<van-col span="6" v-else>

          </van-col>-->
        </van-cell>
    </van-cell-group>
    <div class="empty" v-else>暂无目录</div>
  </div>
</template>

<script>
import { GET_GOODSDETAIL_LIST} from "@/api/course";
export default {
  data() {
    return {
      source:{},
      is_buy:'',
      params: {
        goods_id: this.data,
      },
    };
  },
  props: {
    data: [String, Number]
  },
  mounted() {
    this.loadList();
  },
  methods: {
    loadList() {
      const $this = this;
      GET_GOODSDETAIL_LIST(this.params)
        .then(({ data }) => {
          $this.source = data.konwledge_payment_list;
          $this.is_buy = data.is_buy;
        })
    }
  }
};
</script>

<style  scoped>
</style>